<?php

namespace studioespresso\seofields\jobs;

use craft\elements\Entry;
use craft\queue\BaseJob;
use studioespresso\seofields\models\SeoFieldModel;

class MigrateFieldDataJob extends BaseJob
{

    public $entryId;

    public function init()
    {
        $this->description = "Updating SEO data for {$this->entryId}";
    }


    public function execute($queue)
    {
        $entry = Entry::findOne(['id' => $this->entryId]);
        $model = new SeoFieldModel();
        if($entry->metaTitle) {
            $model->metaTitle = $entry->metaTitle;
        }
        if($entry->metaDescription) {
            $model->metaDescription = $entry->metaDescription;
        }
        if($entry->metaImage) {
            if ($entry->metaImage->one()) {
                $model->facebookImage = [$entry->metaImage->one()->id];
            }
        }
        $entry->setFieldValue('seo', $model);
        if($entry->validate()) {
            \Craft::$app->getElements()->saveElement($entry);
        }
    }

}
