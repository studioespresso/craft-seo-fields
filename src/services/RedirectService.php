<?php

namespace studioespresso\seofields\services;

use Craft;
use craft\base\Component;
use craft\helpers\DateTimeHelper;
use craft\helpers\StringHelper;
use craft\models\Site;
use craft\web\Request;
use studioespresso\seofields\events\RegisterSeoSitemapEvent;
use studioespresso\seofields\models\NotFoundModel;
use studioespresso\seofields\records\NotFoundRecord;
use studioespresso\seofields\records\RedirectRecord;

/**
 * @author    Studio Espresso
 * @package   SeoFields
 * @since     1.0.0
 */
class RedirectService extends Component
{

    public function getAllRedirects()
    {
        return RedirectRecord::find()->all();
    }
}
