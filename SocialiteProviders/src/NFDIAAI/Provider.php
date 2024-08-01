<?php

namespace SocialiteProviders\NFDIAAI;

use GuzzleHttp\RequestOptions;
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
        return $this->buildAuthUrlFromBase('https://regapp.nfdi-aai.de/oidc/realms/nfdi/protocol/openid-connect/auth', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://regapp.nfdi-aai.de/oidc/realms/nfdi/protocol/openid-connect/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://regapp.nfdi-aai.de/oidc/realms/nfdi/protocol/openid-connect/userinfo', [
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
        // dd($user);
        return (new User)->setRaw($user)->map([
            'id' => $user['id'],
            'nickname' => $user['username'],
            'name' => $user['name'],
            'email' => $user['email'],
            'avatar' => $user['avatar'],
        ]);
    }
}
