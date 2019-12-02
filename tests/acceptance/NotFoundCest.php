<?php


class NotFoundCest
{

    public function testNotFound(AcceptanceTester $I)
    {
        $I->amOnPage('/foo');
        $I->verifyRedirect('/foo','http://testing.local.statik.be/bar', 302);
    }
}