<?php

namespace SocialiteProviders\BesteSchule;

use Illuminate\Support\Arr;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'BESTESCHULE';

    /**
     * {@inheritdoc}
     */
    protected $scopes = [''];

    /**
     * {@inheritdoc}
     */
    protected $scopeSeparator = ' ';

    /**
     * {@inheritdoc}
     */
    public static function additionalConfigKeys()
    {
        return [
            'host',
            'authorize_uri',
            'token_uri',
            'userinfo_uri',
            'userinfo_key',
            'user_id',
            'user_nickname',
            'user_name',
            'user_email',
            'user_avatar',
        ];
    }

    /**
     * Get the authentication URL for the provider.
     *
     * @param string $state
     *
     * @return string
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase($this->getBesteSchulePassportUrl('authorize_uri'), $state);
    }

    /**
     * Get the token URL for the provider.
     *
     * @return string
     */
    protected function getTokenUrl()
    {
        return $this->getBesteSchulePassportUrl('token_uri');
    }

    /**
     * Get the raw user for the given access token.
     *
     * @param string $token
     *
     * @return array
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get($this->getBesteSchulePassportUrl('userinfo_uri'), [
            'headers' => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        return (array) json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Map the raw user array to a Socialite User instance.
     *
     * @param array $user
     *
     * @return \BesteSchule\Socialite\User
     */
    protected function mapUserToObject(array $user)
    {
        // The OpenID Connect userinfo endpoint answers with flat claims and no
        // `data` wrapper. Only that shape takes the new path; everything the
        // legacy `api/user` endpoint returns is mapped exactly as before.
        if (! array_key_exists('data', $user) && array_key_exists('sub', $user)) {
            return $this->mapOpenIdUserToObject($user);
        }

        $data = Arr::get($user, 'data', []);

        $name = $data['username']; //default for admin users
        if($data['teacher']) $name = $data['teacher']['forename'] . ' ' . $data['teacher']['name'];
        else if($data['students']) $name = $data['students'][0]['forename'] . ' ' . $data['students'][0]['name'];

        $email = $this->getUserData($data, 'email');
        if(!$email) $email = $data['username'] . '@besteschule.local';

        // The id is the numeric beste.schule user id. Without it (an API that
        // predates the field) the username keeps identifying the account.
        $id = $this->getUserData($data, 'id');

        return (new User())->setRaw($data)->map([
            'id'       => $id !== null && $id !== '' ? (string) $id : $this->getUserData($data, 'username'),
            'nickname' => $this->getUserData($data, 'username'),
            'name'     => $name,
            'email'    => $email,
            'avatar'   => null,
        ]);
    }

    /**
     * Map the flat claims of the OpenID Connect userinfo endpoint.
     *
     * The id is `sub`, the numeric beste.schule user id, exactly what the legacy
     * mapping returns, so an account links to the same person whichever endpoint
     * is configured. The username stays available as nickname. Which claims
     * arrive depends on the granted scopes: `name` is shortened with
     * `profile:redacted`, `roles` needs `roles`, `tenant_*` needs `tenant`,
     * `groups` needs `groups`.
     *
     * @param array $user
     *
     * @return \SocialiteProviders\Manager\OAuth2\User
     */
    protected function mapOpenIdUserToObject(array $user)
    {
        $id = (string) Arr::get($user, 'sub');
        $username = Arr::get($user, 'preferred_username') ?: $id;
        $roles = Arr::get($user, 'roles', []);
        $tenantId = Arr::get($user, 'tenant_id');

        return (new User())->setRaw($user)->map([
            'id'       => $id,
            'nickname' => $username,
            'name'     => Arr::get($user, 'name') ?: $username,
            'email'    => Arr::get($user, 'email') ?: $username . '@besteschule.local',
            'avatar'   => null,
            'role'     => is_array($roles) ? Arr::first($roles) : null,
            'school'   => $tenantId === null ? null : [
                'id'   => $tenantId,
                'name' => Arr::get($user, 'tenant_name'),
            ],
            'groups'   => Arr::get($user, 'groups', []),
        ]);
    }

    /**
     * Get the POST fields for the token request.
     *
     * @param string $code
     *
     * @return array
     */
    protected function getTokenFields($code)
    {
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code',
        ]);
    }

    protected function getBesteSchulePassportUrl($type)
    {
        return rtrim(config('services.besteschule.url', 'https://beste.schule'), '/').'/'.ltrim(($this->getConfig($type, Arr::get([
            'authorize_uri' => 'oauth/authorize',
            'token_uri'     => 'oauth/token',
            'userinfo_uri'  => 'api/user',
        ], $type))), '/');
    }

    protected function getUserData($user, $key)
    {
        return Arr::get($user, $this->getConfig('user_'.$key, $key));
    }
}
