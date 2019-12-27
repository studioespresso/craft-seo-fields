<?php

namespace studioespresso\seofields\services;

use Craft;
use craft\base\Component;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use craft\models\Site;
use craft\web\Request;
use studioespresso\seofields\events\RegisterSeoSitemapEvent;
use studioespresso\seofields\models\NotFoundModel;
use studioespresso\seofields\models\RedirectModel;
use studioespresso\seofields\records\NotFoundRecord;
use studioespresso\seofields\records\RedirectRecord;
use studioespresso\seofields\SeoFields;

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
        $this->handleNotFound($request, $site);
    }

    public function getAllNotFound($orderBy, $siteHandle = null)
    {
        $data = [];
        $query = NotFoundRecord::find();
        $query->orderBy("$orderBy DESC");
        if($siteHandle) {
            $site = Craft::$app->getSites()->getSiteByHandle($siteHandle);
            $query->where(['siteId' => $site->id]);
        }
        foreach ($query->all() as $record) {
            $model = new NotFoundModel();
            $model->setAttributes($record->getAttributes());
            $data[] = $model;

        }
        return $data;
    }

    public function handleNotFound(Request $request, Site $site)
    {
        $notFoundRecord = NotFoundRecord::findOne(['fullUrl' => $request->getAbsoluteUrl(), 'urlPath' => $request->getUrl(), 'siteId' => $site->id]);
        if ($notFoundRecord) {
            $notFoundModel = new NotFoundModel($notFoundRecord->getAttributes());
            $notFoundModel->counter++;
            $notFoundModel->dateLastHit = DateTimeHelper::toIso8601(time());
        } else {
            $notFoundModel = new NotFoundModel();
            $notFoundModel->setAttributes([
                'fullUrl' => $request->getAbsoluteUrl(),
                'urlPath' => $request->getUrl(),
                'siteId' => $site->id,
                'handled' => false,
                'counter' => 1,
                'dateLastHit' => DateTimeHelper::toIso8601(time()),
            ]);
        }

        $redirect = $this->getMatchingRedirect($notFoundModel);
        if ($redirect) {
            $notFoundModel->handled = true;
            $notFoundModel->redirect = $redirect->id;
        }
        $this->saveNotFound($notFoundModel);
        if ($redirect) {
            SeoFields::getInstance()->redirectService->handleRedirect($redirect);
        }

    }

    /**
     * @param NotFoundModel $model
     * @return RedirectModel|false
     */
    public function getMatchingRedirect(NotFoundModel $model)
    {
        $redirect = RedirectRecord::find();
        $redirect->where(['pattern' => $model->urlPath]);

        $redirect->andWhere(Db::parseParam('siteId', [null, $model->siteId], 'in'));
        return $redirect->one() ?? false;
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
        $record->setAttribute('fullUrl', $model->fullUrl);
        $record->setAttribute('urlPath', $model->urlPath);
        $record->setAttribute('counter', $model->counter);
        $record->setAttribute('redirect', $model->redirect);
        $record->setAttribute('handled', $model->handled);
        $record->setAttribute('dateLastHit', $model->dateLastHit);
        $record->setAttribute('handled', $model->handled);
        if ($record->save()) {
            return true;
        }
    }

    public function deletetById($id)
    {
        $record = NotFoundRecord::findOne(['id' => $id]);
        if ($record->delete()) {
            return true;
        }
    }
}
