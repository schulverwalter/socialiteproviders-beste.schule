# BesteSchulePassport

```bash
composer require schulverwalter/besteschule
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'besteschule' => [
  'url' => env('BESTESCHULE_URL'),  
  'client_id' => env('BESTESCHULE_CLIENT_ID'),  
  'client_secret' => env('BESTESCHULE_CLIENT_SECRET'),  
  'redirect' => env('BESTESCHULE_REDIRECT_URI') 
],
```

### Add provider event listener

Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        'SocialiteProviders\\BesteSchule\\BesteSchuleExtendSocialite@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('besteschule')->redirect();
```

### Returned User fields

- ``id`` - the numeric beste.schule user id, as a string. Older versions returned the username here; a consumer that stored it must migrate (the username is still available as ``nickname``)
- ``nickname`` - the username
- ``name``
- ``email`` - if empty ``<username>@besteschule.local``
- ``avatar`` - empty

### OpenID Connect userinfo

By default the provider reads the legacy ``api/user`` endpoint. To read the OpenID Connect userinfo instead, set ``userinfo_uri`` to ``oauth/userinfo`` and request the scopes you need, e.g. ``openid profile:redacted roles tenant groups``. The flat claims are mapped to the same fields, plus ``role``, ``school`` (``id``, ``name``) and ``groups`` in ``$user->attributes``. ``name`` is shortened with ``profile:redacted`` (students ``Anna M.``, teachers ``B. Mayer``) and spelled out with ``profile:names``.
