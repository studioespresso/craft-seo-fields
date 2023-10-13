<?php

namespace studioespresso\seofields\variables;

use Spatie\SchemaOrg\Schema;

class SeoFieldsVariable
{
    public function __call($name, $arguments)
    {
        return Schema::$name();
    }

    public function schema($name, $arguments)
    {
        return Schema::$name();
    }
}
