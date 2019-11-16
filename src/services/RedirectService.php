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
use studioespresso\seofields\models\RedirectModel;
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

    public function saveRedirect(RedirectModel $model)
    {
        $record = false;
        if ($model->id) {
            $record = RedirectRecord::findOne(['id' => $model->id]);
        } else {
            $record = new RedirectRecord();
        }

        $record->setAttribute('siteId', $model->siteId);
        $record->setAttribute('pattern', $model->pattern);
        $record->setAttribute('redirect', $model->redirect);
        $record->setAttribute('method', $model->method);

        if ($record->save()) {
            return true;
        }
    }

    public function deleteRedirectById($id)
    {
        $record = RedirectRecord::findOne(['id' => $id]);
        if ($record->delete()) {
            return true;
        }
    }
}
