<?php

namespace App\Http\Controllers\API;

use App\Events\NewSubmission;
use App\Http\Controllers\Controller;
use App\Models\Entry;
use App\Models\Molecule;
use App\Models\Submission;
use App\Models\User;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SubmissionController extends Controller
{
    /**
     * Report compounds on coconut
     *
     * @return \Illuminate\Http\Response
     */
    public function report(Request $request)
    {
        $user = Auth::user();

        $submission_id = $request->get('submission_id');
        $submission_comments = $request->get('comments');

        if (isset($submission_id)) {
            // retrieve submission under the user?
            $submission = $user->submissions->where('id', $submission_id)->get();
            // check if the submission is still editable?
        } else {
            $reference = strtoupper(Str::random(8));

            $submission = Submission::create([
                'user_id' => $user->id,
                'type' => 'report',
                'reference' => $reference,
                'comment' => $submission_comments,
                'status' => config('app.statuses.STATUS_OPEN'),
            ]);
        }

        $molecules = $request->get('molecules');

        foreach ($molecules as $molecule) {
            $molecule_identifier = $molecule['id'];
            $comments = is_null($molecule['comments']) ? $submission_comments : $molecule['comments'];
            $evidence = is_null($molecule['evidence']) ? '' : $molecule['evidence'];

            $entity = Molecule::with('properties')->where('identifier', $molecule_identifier)->first();
            if ($entity) {
                $entry = Entry::create([
                    'molecule_id' => $entity->id,
                    'identifier' => $entity->identifier,
                    'submission_id' => $submission->id,
                    'evidence' => $evidence,
                    'comment' => $comments,
                ]);
            }
        }

        event(new NewSubmission($submission));

        return $submission;
    }

    /**
     * save the submission request for the curators to approve
     *
     * @return \Illuminate\Http\Response
     */
    public function submission(Request $request)
    {
        $user = Auth::user();

        $data = $request->all();

        $submission_data = $data['submission'];
        $type = $data['type'];

        $comments = null;
        $molecule_id = null;
        $submissions = [];

        if ($type == 'report') {
            foreach ($submission_data as $submission) {
                $molecule_identifier = $submission['id'];
                $comments = $submission['comments'];
                $molecule = Molecule::with('properties')->where('identifier', $molecule_identifier)->first();
                if ($molecule) {
                    $submission = $molecule;
                    $submission['comments'] = $comments;
                    array_push($submissions, $submission);
                }
            }
        } elseif ($type == 'update') {
            $submissions = $submission_data;
        } elseif ($type == 'new') {
            $submissions = $submission_data;
        }

        $reference = strtoupper(Str::random(8));

        $submission = Submission::create([
            'data' => json_encode($submissions),
            'user_id' => $user->id,
            'type' => $type,
            'reference' => $reference,
            'comments' => $comments,
            'molecule_id' => $molecule_id,
            'status' => config('app.statuses.STATUS_OPEN'),
        ]);

        event(new NewSubmission($submission));

        return $submission;
    }
}
