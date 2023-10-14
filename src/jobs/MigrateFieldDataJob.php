<?php

namespace studioespresso\seofields\jobs;

use craft\elements\Entry;
use craft\errors\InvalidFieldException;
use craft\queue\BaseJob;
use studioespresso\seofields\models\SeoFieldModel;

class MigrateFieldDataJob extends BaseJob
{
    public $entry;
    public $fieldHandle;
    public $entryId;
    public $metaTitle;
    public $metaDescription;


    public function init()
    {
        if (!$this->fieldHandle) {
            throw new InvalidFieldException('Field handle not provided');
        }
        $this->entry = Entry::findOne(['id' => $this->entryId]);
        $this->description = "Updating SEO data for '{$this->entry->title}'";
    }


    public function execute($queue)
    {
        $model = new SeoFieldModel();
        if ($this->entry->metaTitle) {
            $model->metaTitle = $this->entry->metaTitle;
        }
        if ($this->entry->metaDescription) {
            $model->metaDescription = $this->entry->metaDescription;
        }
        if ($this->entry->metaImage) {
            if ($this->entry->metaImage->one()) {
                $model->facebookImage = [$this->entry->metaImage->one()->id];
            }
        }
        $this->entry->setFieldValue($this->fieldHandle, $model);
        if ($this->entry->validate()) {
            \Craft::$app->getElements()->saveElement($this->entry);
        }
    }
}
