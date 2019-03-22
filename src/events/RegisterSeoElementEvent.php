<?php

namespace studioespresso\seofields\events;

use yii\base\Event;

/**
 * RegisterSeoElementEvent class.
 * @author    Studio Espresso
 * @package   SeoFields
 * @since     1.0.0
 */
class RegisterSeoElementEvent extends Event
{
    // Properties
    // =========================================================================
    public $elements = [];
}
