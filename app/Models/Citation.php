<?php

namespace App\Models;

use Closure;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\HtmlString;
use OwenIt\Auditing\Contracts\Auditable;

class Citation extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'doi',
        'title',
        'authors',
        'citation_text',
    ];

    /**
     * Get all of the collections that are assigned this citation.
     */
    public function collections(): MorphToMany
    {
        return $this->morphedByMany(Collection::class, 'citable');
    }

    /**
     * Get all of the molecules that are assigned this citation.
     */
    public function molecules(): MorphToMany
    {
        return $this->morphedByMany(Molecule::class, 'citable');
    }

    /**
     * Get all of the citations for the report.
     */
    public function reports(): MorphToMany
    {
        return $this->morphToMany(Report::class, 'reportable');
    }

    public function transformAudit(array $data): array
    {
        return changeAudit($data);
    }

    public static function getForm(): array
    {
        return [
            Section::make()
                ->schema([
                    TextInput::make('failMessage')
                        ->default('')
                        ->hidden()
                        ->disabled(),
                    TextInput::make('doi')
                        ->label('DOI')
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($set, $state) {
                            if (doiRegxMatch($state)) {
                                $set('failMessage', 'Fetching');
                                $citationDetails = fetchDOICitation($state);
                                if ($citationDetails) {
                                    $set('title', $citationDetails['title']);
                                    $set('authors', $citationDetails['authors']);
                                    $set('citation_text', $citationDetails['citation_text']);
                                    $set('failMessage', 'Success');
                                } else {
                                    $set('failMessage', 'No citation found. Please fill in the details manually');
                                }
                            } else {
                                $set('failMessage', 'Invalid DOI');
                            }
                        })
                        ->helperText(function ($get) {

                            if ($get('failMessage') == 'Fetching') {
                                return new HtmlString('<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-dark inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg> ');
                            } elseif ($get('failMessage') != 'Success') {
                                return new HtmlString('<span style="color:red">'.$get('failMessage').'</span>');
                            } else {
                                return null;
                            }
                        })
                        ->required()
                        ->unique()
                        ->rules([
                            fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                if ($get('failMessage') != 'Success') {
                                    $fail($get('failMessage'));
                                }
                            },
                        ])
                        ->validationMessages([
                            'unique' => 'The DOI already exists.',
                        ]),
                ]),

            Section::make()
                ->schema([
                    TextInput::make('title')
                        ->disabled(function ($get, string $operation) {
                            if ($operation = 'edit' || $get('failMessage') == 'No citation found. Please fill in the details manually') {
                                return false;
                            } else {
                                return true;
                            }
                        }),
                    TextInput::make('authors')
                        ->disabled(function ($get, string $operation) {
                            if ($operation = 'edit' || $get('failMessage') == 'No citation found. Please fill in the details manually') {
                                return false;
                            } else {
                                return true;
                            }
                        }),
                    Textarea::make('citation_text')
                        ->label('Citation text / URL')
                        ->disabled(function ($get, string $operation) {
                            if ($operation = 'edit' || $get('failMessage') == 'No citation found. Please fill in the details manually') {
                                return false;
                            } else {
                                return true;
                            }
                        }),
                ])->columns(1),
        ];
    }
}
