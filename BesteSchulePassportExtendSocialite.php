<?php

namespace SocialiteProviders\BesteSchulePassport;

use SocialiteProviders\Manager\SocialiteWasCalled;

class BesteSchulePassportExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('besteschule', Provider::class);
    }
}
