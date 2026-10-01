<?php

namespace studioespresso\seofields\jobs;

use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Queue\Job;
use CraftCms\Cms\Support\Facades\Elements;
use studioespresso\seofields\models\SeoFieldModel;

use function CraftCms\Cms\t;

/**
 * Copies an entry's plain meta fields into its SEO field.
 */
class MigrateFieldDataJob extends Job
{
    /**
     * @param  array{metaTitle: string, metaDescription: string, metaImage: string}  $sources  Source field handles
     */
    public function __construct(
        public int $entryId,
        public int $siteId,
        public string $fieldHandle,
        public array $sources,
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        $entry = Entry::find()->id($this->entryId)->siteId($this->siteId)->status(null)->first();
        if (! $entry) {
            return;
        }

        $value = $entry->getFieldValue($this->fieldHandle);
        $model = $value instanceof SeoFieldModel ? $value : new SeoFieldModel;
        $layout = $entry->getFieldLayout();

        if ($layout->getFieldByHandle($this->sources['metaTitle']) && $title = $entry->getFieldValue($this->sources['metaTitle'])) {
            $model->metaTitle = (string) $title;
        }
        if ($layout->getFieldByHandle($this->sources['metaDescription']) && $description = $entry->getFieldValue($this->sources['metaDescription'])) {
            $model->metaDescription = (string) $description;
        }
        if ($layout->getFieldByHandle($this->sources['metaImage']) && $image = $entry->getFieldValue($this->sources['metaImage'])?->first()) {
            $model->facebookImage = [$image->id];
        }

        $entry->setFieldValue($this->fieldHandle, $model);
        Elements::saveElement($entry);
    }

    protected function defaultDescription(): string
    {
        return t('Updating SEO data for entry {id}', ['id' => $this->entryId], 'seo-fields');
    }
}
