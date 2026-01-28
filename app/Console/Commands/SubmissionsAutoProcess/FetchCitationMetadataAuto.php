<?php

namespace App\Console\Commands\SubmissionsAutoProcess;

use App\Models\Citation;
use App\Models\Collection;
use Illuminate\Bus\Batch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FetchCitationMetadataAuto extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coconut:fetch-citation-metadata {collection_id? : The ID of the collection to fetch metadata for} {--all : Process all citations that need metadata} ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch citation metadata from external APIs for citations that need it';

    /**
     * Configuration variables for easy tuning
     */
    private $batchSize = 100; // Number of citations per batch job

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $collection_id = $this->argument('collection_id');
        $all = $this->option('all');

        if (! $collection_id && ! $all) {
            Log::error('Please specify either a collection_id or use --all flag');

            return 1;
        }

        if ($collection_id) {
            $collection = Collection::find($collection_id);
            if (! $collection) {
                Log::error("Collection with ID {$collection_id} not found.");

                return 1;
            }
            Log::info("Fetching citation metadata for collection ID: {$collection_id}");
        } else {
            Log::info('Fetching citation metadata for all citations');
        }

        // If collection_id is set, extract DOIs from meta_data of entries in that collection
        if ($collection_id) {
            $entrySql = 'SELECT meta_data FROM entries WHERE collection_id = ? AND meta_data IS NOT NULL';
            $entries = DB::select($entrySql, [$collection_id]);
            $dois = [];
            foreach ($entries as $entry) {
                $meta = is_string($entry->meta_data) ? json_decode($entry->meta_data, true) : (array) $entry->meta_data;
                if (isset($meta['new_molecule_data']['references']) && is_array($meta['new_molecule_data']['references'])) {
                    foreach ($meta['new_molecule_data']['references'] as $ref) {
                        if (isset($ref['doi']) && $ref['doi']) {
                            $dois[] = $ref['doi'];
                        }
                    }
                }
            }
            $dois = array_unique($dois);
            if (empty($dois)) {
                Log::info("No DOIs found in meta_data for collection ID {$collection_id}.");

                return 0;
            }
            // Now fetch citation IDs for these DOIs with the required conditions, in manageable chunks
            $ids = [];
            $chunkSize = 500;
            foreach (array_chunk($dois, $chunkSize) as $doiChunk) {
                $placeholders = str_repeat('?,', count($doiChunk) - 1).'?';
                $sql = "SELECT id FROM citations WHERE doi IN ($placeholders) "
                    ." AND ( (title IS NULL OR title = '') "
                    ." AND (authors IS NULL OR authors = '') "
                    ." AND (citation_text IS NULL OR citation_text = '') ) ORDER BY id";
                $ids = array_merge($ids, DB::select($sql, $doiChunk));
            }
        } else {
            // All citations logic as before
            $rawSql = 'SELECT id FROM citations '
                ." WHERE doi IS NOT NULL AND doi != '' "
                ." AND ( (title IS NULL OR title = '') "
                ." AND (authors IS NULL OR authors = '') "
                ." AND (citation_text IS NULL OR citation_text = '') ) ";
            $ids = DB::select($rawSql.' ORDER BY id');
        }

        $idArray = array_map(function ($row) {
            return $row->id;
        }, $ids);
        $totalCount = count($idArray);
        Log::info("Found {$totalCount} citations that need metadata fetching");
        if ($totalCount === 0) {
            Log::info('No citations found that need metadata fetching.');

            return 0;
        }

        $chunks = array_chunk($idArray, $this->batchSize);
        $progressBar = $this->output->createProgressBar(count($chunks));
        $progressBar->setFormat(' %current%/%max% batches [%bar%] %percent%% %elapsed%/%estimated% %memory%');
        $progressBar->start();
        foreach ($chunks as $chunk) {
            $citations = Citation::whereIn('id', $chunk)->get();
            foreach ($citations as $citation) {
                try {
                    Log::info("Fetching metadata for citation {$citation->id} with DOI: {$citation->doi}");
                    $citationResponse = $this->fetchCitationFromAPIs($citation->doi);
                    if ($citationResponse) {
                        $citation->update($citationResponse);
                        Log::info("Successfully fetched metadata for citation {$citation->id}");
                    } else {
                        Log::warning("No metadata found for citation {$citation->id} with DOI: {$citation->doi}");
                    }
                } catch (\Throwable $e) {
                    Log::error("Failed to fetch metadata for citation {$citation->id}: ".$e->getMessage());
                }
            }
            $progressBar->advance();
        }
        $progressBar->finish();
        $this->newLine();

        Log::info('Citation metadata fetch process started.');

    }

    /**
     * Fetch citation metadata from external APIs (inline from FetchCitationMetadata)
     */
    private function fetchCitationFromAPIs($doi): ?array
    {
        $citationResponse = null;

        // Try EuropePMC first
        $europemcUrl = config('services.citation.europepmc_url');
        $europemcParams = [
            'query' => 'DOI:'.$doi,
            'format' => 'json',
            'pageSize' => '1',
            'resulttype' => 'core',
            'synonym' => 'true',
        ];
        $response = $this->makeRequest($europemcUrl, $europemcParams);
        $europemcResponse = ($response && method_exists($response, 'json')) ? $response->json() : null;

        if ($europemcResponse && isset($europemcResponse['resultList']['result']) && count($europemcResponse['resultList']['result']) > 0) {
            $citationResponse = $this->formatCitationResponse($europemcResponse['resultList']['result'][0], 'europemc');
        } else {
            // Try CrossRef
            $crossrefUrl = config('services.citation.crossref_url').$doi;
            $response = $this->makeRequest($crossrefUrl);
            $crossrefResponse = ($response && method_exists($response, 'json')) ? $response->json() : null;
            if ($crossrefResponse && isset($crossrefResponse['message'])) {
                $citationResponse = $this->formatCitationResponse($crossrefResponse['message'], 'crossref');
            } else {
                // Try DataCite as last resort
                $dataciteUrl = config('services.citation.datacite_url').$doi;
                $response = $this->makeRequest($dataciteUrl);
                $dataciteResponse = ($response && method_exists($response, 'json')) ? $response->json() : null;
                if ($dataciteResponse && isset($dataciteResponse['data'])) {
                    $citationResponse = $this->formatCitationResponse($dataciteResponse['data'], 'datacite');
                }
            }
        }

        return $citationResponse;
    }

    /**
     * Make an HTTP request (inline from FetchCitationMetadata)
     */
    private function getRateLimiterConfig($url)
    {
        if (strpos($url, 'api.crossref.org') !== false) {
            return [
                'key' => 'crossref-api',
                'limit' => 45, // 45 requests per minute (as before)
                'duration' => 60,
                'api_name' => 'CrossRef',
            ];
        }
        if (strpos($url, 'api.datacite.org') !== false) {
            // DataCite: Conservative limit per official docs: 500 requests per 5 minutes (100 per minute)
            return [
                'key' => 'datacite-api',
                'limit' => 100, // 100 requests per minute (conservative, well below 3000/5min hard limit)
                'duration' => 60,
                'api_name' => 'DataCite',
            ];
        }

        // EuropePMC doesn't need rate limiting
        return null;
    }

    private function makeRequest($url, $params = [])
    {
        $rateLimiterConfig = $this->getRateLimiterConfig($url);
        if ($rateLimiterConfig) {
            $response = \Illuminate\Support\Facades\RateLimiter::attempt(
                $rateLimiterConfig['key'],
                $rateLimiterConfig['limit'],
                function () use ($url, $params, $rateLimiterConfig) {
                    Log::info("Making rate-limited {$rateLimiterConfig['api_name']} request: {$url}");

                    return $this->doActualRequest($url, $params);
                },
                $rateLimiterConfig['duration']
            );
            if ($response === false) {
                $waitTime = \Illuminate\Support\Facades\RateLimiter::availableIn($rateLimiterConfig['key']);
                Log::warning("{$rateLimiterConfig['api_name']} rate limited (wait: {$waitTime}s). Skipping request for: {$url}");

                return null;
            }
            if ($response === true || ! is_object($response) || ! method_exists($response, 'json')) {
                Log::warning("Rate limiter returned invalid response type for {$rateLimiterConfig['api_name']}: ".gettype($response));

                return null;
            }

            return $response;
        }

        return $this->doActualRequest($url, $params);
    }

    private function doActualRequest($url, $params = [])
    {
        $maxRetries = 3;
        $baseDelay = 1.0;
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $response = \Illuminate\Support\Facades\Http::timeout(120)
                    ->connectTimeout(60)
                    ->get($url, $params);
                if ($response->successful()) {
                    return $response;
                }
                Log::warning("HTTP request failed for URL: {$url}, Status: ".$response->status()." (attempt $attempt/$maxRetries)");
            } catch (\Exception $e) {
                Log::warning("HTTP request exception (attempt $attempt/$maxRetries) for URL: {$url}, Error: ".$e->getMessage());
                if ($attempt < $maxRetries && (
                    strpos($e->getMessage(), 'SSL routines::unexpected eof') !== false ||
                    strpos($e->getMessage(), 'Connection timeout') !== false ||
                    strpos($e->getMessage(), 'Connection reset') !== false ||
                    strpos($e->getMessage(), 'timeout') !== false
                )) {
                    $delay = $baseDelay * $attempt + (rand(0, 500) / 1000);
                    Log::info("Retrying after {$delay}s due to connection issue...");
                    usleep((int) ($delay * 1000000));

                    continue;
                }
            }
            if ($attempt < $maxRetries) {
                $delay = $baseDelay * $attempt;
                usleep((int) ($delay * 1000000));
            }
        }

        return null;
    }

    /**
     * Format citation response based on API type (inline from FetchCitationMetadata)
     */
    private function formatCitationResponse($obj, $apiType): array
    {
        $journalTitle = '';
        $yearofPublication = '';
        $volume = '';
        $issue = '';
        $pageInfo = '';
        $formattedCitationRes = [];

        if ($obj) {
            switch ($apiType) {
                case 'europemc':
                    $journalTitle = isset($obj['journalInfo']['journal']['title']) ? $obj['journalInfo']['journal']['title'] : '';
                    $yearofPublication = isset($obj['journalInfo']['yearOfPublication']) ? $obj['journalInfo']['yearOfPublication'] : '';
                    $volume = isset($obj['journalInfo']['volume']) ? $obj['journalInfo']['volume'] : '';
                    $issue = isset($obj['journalInfo']['issue']) ? $obj['journalInfo']['issue'] : '';
                    $pageInfo = isset($obj['pageInfo']) ? $obj['pageInfo'] : '';
                    $formattedCitationRes['title'] = isset($obj['title']) ? $obj['title'] : '';
                    $formattedCitationRes['authors'] = isset($obj['authorString']) ? $obj['authorString'] : '';
                    $formattedCitationRes['citation_text'] = $journalTitle.' '.$yearofPublication.' '.$volume.' ( '.$issue.' ) '.$pageInfo;
                    break;
                case 'datacite':
                    $journalTitle = isset($obj['attributes']['titles'][0]['title']) ? $obj['attributes']['titles'][0]['title'] : '';
                    $yearofPublication = isset($obj['attributes']['publicationYear']) ? $obj['attributes']['publicationYear'] : null;
                    $volume = isset($obj['attributes']['volume']) ? $obj['attributes']['volume'] : '';
                    $issue = isset($obj['attributes']['issue']) ? $obj['attributes']['issue'] : '';
                    $pageInfo = isset($obj['attributes']['page']) ? $obj['attributes']['page'] : '';
                    $formattedCitationRes['title'] = $journalTitle;
                    if (isset($obj['attributes']['creators'])) {
                        $formattedCitationRes['authors'] = implode(', ', array_map(function ($author) {
                            return $author['name'];
                        }, $obj['attributes']['creators']));
                    }
                    $formattedCitationRes['citation_text'] = $journalTitle.' '.$yearofPublication;
                    break;
                case 'crossref':
                    $journalTitle = isset($obj['title'][0]) ? $obj['title'][0] : '';
                    $yearofPublication = isset($obj['published-online']['date-parts'][0][0]) ? $obj['published-online']['date-parts'][0][0] : '';
                    $volume = isset($obj['volume']) ? $obj['volume'] : '';
                    $issue = isset($obj['issue']) ? $obj['issue'] : '';
                    $pageInfo = isset($obj['page']) ? $obj['page'] : '';
                    $formattedCitationRes['title'] = $journalTitle;
                    if (isset($obj['author'])) {
                        $formattedCitationRes['authors'] = implode(', ', array_map(function ($author) {
                            $fullName = '';
                            if (isset($author['given'])) {
                                $fullName .= $author['given'].' ';
                            }
                            if (isset($author['family'])) {
                                $fullName .= $author['family'];
                            }

                            return trim($fullName);
                        }, $obj['author']));
                    }
                    $formattedCitationRes['citation_text'] = $journalTitle.' '.$yearofPublication.' '.$volume.' ( '.$issue.' ) '.$pageInfo;
                    break;
            }
        }

        return $formattedCitationRes;
    }
}
