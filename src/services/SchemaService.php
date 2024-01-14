<?php

namespace studioespresso\seofields\services;

use Craft;
use craft\base\Component;
use craft\base\Element;
use craft\commerce\elements\Product;
use craft\commerce\Plugin as Commerce;
use craft\commerce\services\ProductTypes;
use craft\elements\Category;
use craft\elements\Entry;
use Spatie\SchemaOrg\Schema;
use studioespresso\seofields\SeoFields;

/**
 * @author    Studio Espresso
 * @package   SeoFields
 * @since     4.0.0
 */
class SchemaService extends Component
{
    public function getSchema(Element $element): Schema|null
    {
        $settings = $this->siteDefault->getSchema();
        try {
            switch (get_class($element)) {
                case Entry::class:
                    $schemaSettings = $settings['sections'];
                    $sectionId = $element->section->id;
                    $schemaClass = $schemaSettings[$sectionId];

                    /** @var $schema Schema */
                    $schema = Craft::createObject($schemaClass);
                    $schema->name($this->getMetaTitle($element, false) ?? "");
                    $schema->description($this->getMetaDescription() ?? "");
                    $schema->url($element->getUrl() ?? "");
                    break;
                case Category::class:
                    $schemaSettings = $settings['groups'];
                    $groupId = $element->group->id;
                    $schemaClass = $schemaSettings[$groupId];

                    /** @var $schema Schema */
                    $schema = Craft::createObject($schemaClass);
                    $schema->name($this->getMetaTitle($element, false) ?? "");
                    $schema->description($this->getMetaDescription() ?? "");
                    $schema->url($element->getUrl() ?? "");
                    break;
            }
            return $schema;
        } catch (\Exception $e) {
            Craft::error($e, SeoFields::class);
            return null;
        }
    }

    public function getDefaultOptions()
    {
        $options = SeoFields::getInstance()->getSettings()->schemaOptions;
        return array_merge([
            get_class(Schema::webPage()) => 'WebPage',
            get_class(Schema::article()) => 'Article',
            get_class(Schema::creativeWork()) => 'Creative Work',
            get_class(Schema::review()) => 'Review',
            get_class(Schema::organization()) => 'Organisation',
            get_class(Schema::recipe()) => 'Recipe',
            get_class(Schema::person()) => 'Person',
        ], $options);
    }

    public function schema()
    {
        return new Schema();
    }
}
