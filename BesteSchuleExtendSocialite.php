<?php

namespace SocialiteProviders\BesteSchule;

use SocialiteProviders\Manager\SocialiteWasCalled;

class BesteSchuleExtendSocialite
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
