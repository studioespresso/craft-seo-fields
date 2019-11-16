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

/**
 * @author    Studio Espresso
 * @package   SeoFields
 * @since     1.0.0
 */
class NotFoundService extends Component
{
    public function handleNotFoundException()
    {
        $request = Craft::$app->getRequest();
        if ($request->isLivePreview || $request->isCpRequest || $request->isConsoleRequest) {
            return;
        }
        $site = Craft::$app->getSites()->getCurrentSite();
        $this->notFoundHandle($request, $site);
    }

    public function getAllNotFound($orderBy)
    {
        return NotFoundRecord::find()->orderBy("$orderBy DESC")->all();
    }

    public function notFoundHandle(Request $request, Site $site)
    {
        $notFoundRecord = NotFoundRecord::findOne(['url' => $request->getAbsoluteUrl(), 'siteId' => $site->id]);
        if ($notFoundRecord) {
            $notFoundModel = new NotFoundModel($notFoundRecord->getAttributes());
            $notFoundModel->counter++;
        } else {
            $notFoundModel = new NotFoundModel();
            $notFoundModel->setAttributes([
                'url' => $request->getAbsoluteUrl(),
                'siteId' => $site->id,
                'handled' => false,
                'counter' => 1,
                'dateLastHit' => DateTimeHelper::toIso8601(time()),
            ]);
        }

        $this->saveNotFound($notFoundModel);

    }


    private function saveNotFound(NotFoundModel $model)
    {
        $record = false;
        if (isset($model->id)) {
            $record = NotFoundRecord::findOne([
                'id' => $model->id
            ]);
        }

        if (!$record) {
            $record = new NotFoundRecord();
            $record->uid = StringHelper::UUID();
        }

        $record->setAttribute('siteId', $model->siteId);
        $record->setAttribute('url', $model->url);
        $record->setAttribute('counter', $model->counter);
        $record->setAttribute('dateLastHit', $model->dateLastHit);
        $record->setAttribute('handled', $model->handled);
        if ($record->save()) {
            return true;
        }
    }
}
