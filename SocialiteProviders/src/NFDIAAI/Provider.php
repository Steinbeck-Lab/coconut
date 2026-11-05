<?php

namespace SocialiteProviders\NFDIAAI;

use GuzzleHttp\RequestOptions;
use InvalidArgumentException;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    const IDENTIFIER = 'NFDI-AAI';

    /**
     * {@inheritdoc}
     */
    protected $scopes = ['basic'];

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(config('services.regapp.oidc_base_url').'/auth', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return config('services.regapp.oidc_base_url').'/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(config('services.regapp.oidc_base_url').'/userinfo', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        if (! isset($user['voperson_id']) || empty($user['voperson_id'])) {
            throw new InvalidArgumentException(
                'The user data is invalid: a unique "id" is required. User Data: '.json_encode($user)
            );
        }

        return (new User)->setRaw($user)->map([
            'id' => $user['voperson_id'],
            'nickname' => $user['given_name'],
            'name' => $user['name'],
            'email' => $user['email'],
            'avatar' => isset($user['avatar']) ? $user['avatar'] : null,
        ]);
    }
}
