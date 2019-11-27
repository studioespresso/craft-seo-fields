<?php

namespace myprojecttests;

use Craft;
use craft\elements\User;
use FunctionalTester;
use studioespresso\seofields\models\NotFoundModel;
use studioespresso\seofields\records\NotFoundRecord;
use studioespresso\seofields\SeoFields;


class NotFoundCest
{
    // Public methods
    // =========================================================================
    public function _before(FunctionalTester $I)
    {

    }
    // Tests
    // =========================================================================
    /**
     * @param FunctionalTester $I
     */
    public function testNotFoundTracking(FunctionalTester $I)
    {
        $I->amOnPage('/foo');
        $I->canSeeRecord(NotFoundRecord::class, ['urlPath' => '/foo']);
    }
}
