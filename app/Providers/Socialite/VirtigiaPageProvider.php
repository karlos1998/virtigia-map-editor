<?php


namespace App\Providers\Socialite;

use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\User;

class VirtigiaPageProvider extends AbstractProvider
{
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(config('services.virtigia_page.url') . '/oauth/authorize', $state);
    }

    protected function getTokenUrl()
    {
        return config('services.virtigia_page.url') . '/oauth/token';
    }

    protected function getUserByToken($token)
    {
        dd($token);
        $response = $this->getHttpClient()->get(config('services.virtigia_page.url') . '/api/user', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
        ]);
    }

    protected function getTokenFields($code): array
    {
        return [
            'grant_type' => 'authorization_code',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUrl,
            'code' => $code,
        ];
    }
}
