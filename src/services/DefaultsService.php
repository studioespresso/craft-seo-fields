<?php

namespace studioespresso\seofields\services;

use craft\models\Site;
use studioespresso\seofields\models\SeoDefaultsModel;
use studioespresso\seofields\records\DefaultsRecord;
use studioespresso\seofields\SeoFields;

use Craft;
use craft\base\Component;

/**
 * @author    Studio Espresso
 * @package   SeoFields
 * @since     1.0.0
 */
class DefaultsService extends Component
{
    // Public Methods
    // =========================================================================
    public function exampleService()
    {
        $result = 'something';

        return $result;
    }

    public function getDefaultsBySite(Site $site) {
        $record = DefaultsRecord::findOne(
            ['siteId' => $site->id]
        );
        if($record) {
            return new SeoDefaultsModel($record->getAttributes());
        } else {
            return new SeoDefaultsModel();
        }
    }
}
