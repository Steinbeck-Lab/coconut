<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Auth;
use Illuminate\Auth\Events\Registered;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

class SocialController extends Controller
{
    /**
     * Redirect the user to the GitHub authentication page.
     *
     * @return \Illuminate\Http\Response
     */
    public function redirectToProvider($service)
    {
        return Socialite::driver($service)->redirect();
    }

    /**
     * Obtain the user information from GitHub.
     *
     * @return \Illuminate\Http\Response
     */
    public function handleProviderCallback($service)
    {
        try {
            $providerUser = Socialite::driver($service)->user();
        } catch (InvalidStateException $e) {
            $providerUser = Socialite::driver($service)->stateless()->user();
        }

        $linkedSocialAccount = \App\Models\LinkedSocialAccount::where('provider_name', $service)
            ->where('provider_id', $providerUser->getId())
            ->first();

        $user = null;

        if ($linkedSocialAccount) {
            $user = $linkedSocialAccount->user;
        } else {
            $email = $providerUser->getEmail();
            $name = $providerUser->getName();

            if ($email) {
                $user = User::where('email', $email)->first();
            }
            if (! $user) {
                $user = tap(User::create([
                    'name' => $name,
                    'email' => $email,
                ]), function (User $user) {
                    event(new Registered($user));
                });
            }
            $user->linkedSocialAccounts()->create([
                'provider_id' => $providerUser->getId(),
                'provider_name' => $service,
            ]);
        }

        Auth::login($user);

        return redirect('/dashboard');
    }
}
