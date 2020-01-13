<?php

namespace studioespresso\seofields\services;

use Craft;
use craft\base\Component;
use craft\helpers\DateTimeHelper;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\models\Site;
use craft\web\Request;
use studioespresso\seofields\events\RegisterSeoSitemapEvent;
use studioespresso\seofields\models\NotFoundModel;
use studioespresso\seofields\models\RedirectModel;
use studioespresso\seofields\records\NotFoundRecord;
use studioespresso\seofields\records\RedirectRecord;
use yii\base\Exception;
use yii\base\ExitException;

/**
 * @author    Studio Espresso
 * @package   SeoFields
 * @since     1.0.0
 */
class RedirectService extends Component
{

    public function handleRedirect(RedirectRecord $redirect)
    {
        $model = new RedirectModel($redirect->getAttributes());
        $this->updateOnRedirect($model);
        $this->redirect($model);

        try {
            Craft::$app->end();
        } catch (ExitException $e) {
            Craft::error($e->getMessage(), __METHOD__);
        }
    }

    public function getAllRedirects()
    {
        return RedirectRecord::find()->all();
    }

    private function updateOnRedirect(RedirectModel $model)
    {
        $model->counter++;
        $model->dateLastHit = DateTimeHelper::toIso8601(time());
        $model->validate();
        $this->saveRedirect($model);
    }

    public function saveRedirect(RedirectModel $model)
    {
        $record = false;
        if ($model->id) {
            $record = RedirectRecord::findOne(['id' => $model->id]);
        } else {
            $record = new RedirectRecord();
        }


        $record->setAttribute('siteId', $model->siteId === "0" ? null : $model->siteId);
        $record->setAttribute('pattern', $model->pattern);
        $record->setAttribute('sourceMatch', $model->sourceMatch);
        $record->setAttribute('redirect', $model->redirect);
        $record->setAttribute('matchType', $model->matchType);
        $record->setAttribute('counter', $model->counter);
        $record->setAttribute('dateLastHit', $model->dateLastHit);
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

    private function redirect(RedirectModel $redirectModel)
    {
        try {
            $url = UrlHelper::siteUrl($redirectModel->redirect, null, null, $redirectModel->siteId);
        } catch (yii\base\Exception $e) {
        }
        $response = Craft::$app->response;
        $response->redirect($url, $redirectModel->method)->send();
    }
}
