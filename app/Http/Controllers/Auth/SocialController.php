<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

class SocialController extends Controller
{
    /**
     * Redirect the user to the GitHub authentication page.
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function redirectToProvider($service)
    {
        return Socialite::driver($service)->redirect();
    }

    /**
     * Obtain the user information from GitHub.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handleProviderCallback($service)
    {
        try {
            $providerUser = Socialite::driver($service)->user();
        } catch (InvalidStateException $e) {
            /** @var \Laravel\Socialite\Two\AbstractProvider $driver */
            $driver = Socialite::driver($service);
            $providerUser = $driver->stateless()->user();
        }

        $linkedSocialAccount = \App\Models\LinkedSocialAccount::where('provider_name', $service)
            ->where('provider_id', $providerUser->getId())
            ->first();

        $user = null;

        if ($linkedSocialAccount) {
            /** @var User $user */
            $user = $linkedSocialAccount->user()->first();
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
