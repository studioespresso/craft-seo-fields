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

    public function saveDefaults(SeoDefaultsModel $model, $siteId)
    {
        $record = DefaultsRecord::findOne([
            'siteId' => $siteId
        ]);

        if (!$record) {
            $record = new DefaultsRecord();
        }
        $record->setAttribute('defaultMeta', $model->toArray(['defaultSiteTitle', 'titleSeperator']));
        $record->setAttribute('siteId', $model->siteId);

        if ($record->validate()) {
            $record->save();
            return true;
        }

    }

    public function getDataBySite(Site $site)
    {
        $record = DefaultsRecord::findOne(
            ['siteId' => $site->id]
        );
        if ($record) {
            $model = new SeoDefaultsModel();
            $model->setAttributes(json_decode($record->getAttribute("defaultMeta"), true));
            return $model;
        } else {
            return new SeoDefaultsModel();
        }
    }
}
