<?php

namespace studioespresso\seofields\services;

use craft\base\Component;
use Spatie\SchemaOrg\Schema;
use studioespresso\seofields\SeoFields;

/**
 * @author    Studio Espresso
 * @package   SeoFields
 * @since     4.0.0
 */
class SchemaService extends Component
{
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
