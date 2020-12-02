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
use studioespresso\seofields\SeoFields;
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
        Craft::debug("Found a redirect for this 404, redirecting", SeoFields::class);
        $model = new RedirectModel($redirect->getAttributes());
        $this->updateOnRedirect($model);
        $this->redirect($model);

        try {
            Craft::$app->end();
        } catch (ExitException $e) {
            Craft::error($e->getMessage(), __METHOD__);
        }
    }

    public function getRedirectById($id)
    {
        $record = RedirectRecord::findOne(['id' => $id]);
        $model = new RedirectModel();
        $model->setAttributes($record->getAttributes());
        return $model;
    }

    public function getAllRedirects($searchParam = null)
    {
        $query = RedirectRecord::find();
        if ($searchParam) {
            $query->where(['like', 'pattern', $searchParam]);
        }
        return $query->all();
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
        if (substr($model->pattern, 0, 1) == '/') {
            $record->setAttribute('pattern', $model->pattern);
        } else {
            $record->setAttribute('pattern', "/$model->pattern");
        }
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
            $response = Craft::$app->response;
            $response->redirect($url, $redirectModel->method)->send();
        } catch (\Exception $e) {
        }
        return;
    }
}
