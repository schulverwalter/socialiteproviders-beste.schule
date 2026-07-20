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
     * beste.schule gates its API behind explicit scopes: `read` for GET
     * requests, `write` for state-changing ones. Consumers of this provider
     * pull data from the API (starting with the /api/user profile call this
     * provider itself makes), so both are requested by default - existing
     * applications keep full API access by simply updating the package.
     * Override with ->scopes([...]) / ->setScopes([...]) for finer grants.
     *
     * {@inheritdoc}
     */
    protected $scopes = ['read', 'write'];

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
        $data = Arr::get($user, 'data', []);

        $name = $data['username'] ?? null; //default for admin users
        if(!empty($data['teacher'])) $name = $data['teacher']['forename'] . ' ' . $data['teacher']['name'];
        else if(!empty($data['students'])) $name = $data['students'][0]['forename'] . ' ' . $data['students'][0]['name'];

        $email = $this->getUserData($data, 'email');
        if(!$email) $email = $data['username'] . '@besteschule.local';

        return (new User())->setRaw($data)->map([
            'id'       => $this->getUserData($data, 'username'),
            'nickname' => $this->getUserData($data, 'username'),
            'name'     => $name,
            'email'    => $email,
            'avatar'   => null,
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
