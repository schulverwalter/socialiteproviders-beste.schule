# BesteSchulePassport

```bash
composer require schulverwalter/besteschule
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'besteschule' => [
  'client_url' => env('BESTESCHULE_URL'),  
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

### Scopes

beste.schule gates its API behind explicit OAuth scopes. By default this
provider requests `read` and `write`, which grants the same full API access
integrations had before the scopes were introduced (and covers the
`/api/user` call the provider makes itself). To request a different set,
e.g. read-only access:

```php
return Socialite::driver('besteschule')->setScopes(['read'])->redirect();
```

### Returned User fields

- ``id`` - equal to ``nickname``
- ``nickname``
- ``name``
- ``email`` - if empty ``<username>@besteschule.test``
- ``avatar`` - empty
