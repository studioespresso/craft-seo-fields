<?php

namespace studioespresso\seofields\services;

use craft\base\Component;
use craft\commerce\elements\Product;
use craft\commerce\Plugin as Commerce;
use craft\commerce\services\ProductTypes;
use Spatie\SchemaOrg\Schema;

/**
 * @author    Studio Espresso
 * @package   SeoFields
 * @since     4.0.0
 */
class SchemaService extends Component
{
    public function getDefaultOptions()
    {
        return [
            get_class(Schema::webPage()) => 'WebPage',
            get_class(Schema::article()) => 'Article',
            get_class(Schema::creativeWork()) => 'Creative Work',
            get_class(Schema::review()) => 'Review',
            get_class(Schema::organization()) => 'Organisation',

        ];
    }
}
