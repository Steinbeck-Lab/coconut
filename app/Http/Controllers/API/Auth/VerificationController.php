<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Auth;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    public function verify($user_id, Request $request)
    {
        if (! $request->hasValidSignature()) {
            return response()->json(['msg' => 'Invalid/Expired url provided.'], 401);
        }

        if ($request->user() && $request->user()->getKey() != $user_id) {
            Auth::logout();
            throw new AuthorizationException;
        }

        $user = User::findOrFail($user_id);

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        return redirect()->route('welcome')->with('success', 'Email verification Successful');
    }

    public function resend()
    {
        if (auth()->user()) {
            // if (auth()->user()->hasVerifiedEmail()) {
            //     return response()->json(['msg' => 'Email already verified.'], 400);
            // }

            auth()->user()->sendEmailVerificationNotification();

            return response()->json(['msg' => 'Email verification link sent on your email id']);
        }
    }
}
