<?php

namespace studioespresso\seofields\records;

use craft\db\ActiveRecord;

/***
 * @author    Studio Espresso
 * @package   SeoFields
 * @since     1.0.0
 */
class DefaultsRecord extends ActiveRecord
{
    // Public Static Methods
    // =========================================================================
    public static function tableName()
    {
        return '{{%seofields_defaults}}';
    }
}
