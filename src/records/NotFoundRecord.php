<?php

namespace studioespresso\seofields\records;

use craft\db\ActiveRecord;

/***
 * @author    Studio Espresso
 * @package   SeoFields
 * @since     1.0.0
 *
 * @property $id int
 * @property $siteId int
 * @property $fullUrl string
 * @property $urlPath string
 * @property $urlParams string
 * @property $referrer string
 * @property $handled bool
 * @property $counter int
 * @property $redirect int
 * @property $dateLastHit DateTime
 */
class NotFoundRecord extends ActiveRecord
{
    // Public Static Methods
    // =========================================================================
    public static function tableName()
    {
        return '{{%seofields_404}}';
    }
}
