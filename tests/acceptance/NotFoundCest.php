<?php


class NotFoundCest
{

    /**
     * @internal blaaa
     * @param AcceptanceTester $I
     */
    public function testNotFound302(AcceptanceTester $I)
    {
        $I->verifyRedirect('/temp-redirect','http://testing.local.statik.be/bar', 302);
    }

    public function testNotFound301(AcceptanceTester $I) {
        $I->verifyRedirect('/permanent-redirect','http://testing.local.statik.be/bar', 301);
    }
}