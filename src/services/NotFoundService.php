<?php

namespace studioespresso\seofields\services;

use Craft;
use craft\base\Component;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use craft\models\Site;
use craft\web\Request;
use studioespresso\seofields\models\NotFoundModel;
use studioespresso\seofields\models\RedirectModel;
use studioespresso\seofields\records\NotFoundRecord;
use studioespresso\seofields\records\RedirectRecord;
use studioespresso\seofields\SeoFields;
use yii\base\Exception;

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
        if ($request->isLivePreview || $request->isCpRequest || $request->isConsoleRequest || substr($request->getFullPath(), 0, 11) === 'cpresources') {
            return;
        }
        $site = Craft::$app->getSites()->getCurrentSite();
        $this->handleNotFound($request, $site);
    }

    public function getAllNotFound($orderBy, $siteHandle = null, $handled)
    {
        $data = [];
        $query = NotFoundRecord::find();
        $query->orderBy("$orderBy DESC, dateLastHIT DESC");
        $query->where(['in', 'siteId', Craft::$app->getSites()->getEditableSiteIds()]);
        if ($siteHandle) {
            $site = Craft::$app->getSites()->getSiteByHandle($siteHandle);
            $query->andWhere(['siteId' => $site->id]);
        }

        if ($handled !== "all") {
            $query->andWhere(Db::parseParam('handled', $handled));
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
        try {
            $notFoundRecord = NotFoundRecord::findOne(['fullUrl' => $request->getAbsoluteUrl(), 'urlPath' => $request->getUrl(), 'siteId' => $site->id]);
            if ($notFoundRecord) {
                Craft::debug("Updating excisting 404", SeoFields::class);
                $notFoundModel = new NotFoundModel($notFoundRecord->getAttributes());
                $notFoundModel->counter++;
                $notFoundModel->dateLastHit = DateTimeHelper::toIso8601(time());
            } else {
                Craft::debug("First time we see this 404, saving new record", SeoFields::class);
                $notFoundModel = new NotFoundModel();
                $notFoundModel->setAttributes([
                    'fullUrl' => urldecode($request->getAbsoluteUrl()),
                    'urlPath' => urldecode($request->getUrl()),
                    'referrer' => $request->referrer,
                    'urlParams' => $request->queryStringWithoutPath,
                    'siteId' => $site->id,
                    'handled' => false,
                    'counter' => 1,
                    'dateLastHit' => DateTimeHelper::toIso8601(time()),
                ]);
            }

            $redirect = $this->getMatchingRedirect($notFoundModel);
            if ($redirect) {
                if (is_array($redirect)) {
                    $record = $redirect['record'];
                    $notFoundModel->redirect = $record->id;
                } else {
                    $notFoundModel->redirect = $redirect->id;
                }
                $notFoundModel->handled = true;
            }

            $this->saveNotFound($notFoundModel);

            if ($redirect) {
                SeoFields::getInstance()->redirectService->handleRedirect($redirect);
            }

            $this->shouldWeCleanupRedirects();
        } catch (Exception $e) {
            Craft::error($e->getMessage(), 'seo-fields');
        }
    }

    public function markAsHandled(NotFoundRecord|int $record): void
    {
        if (is_int($record)) {
            $query = NotFoundRecord::find();
            $query->where(['id' => $record]);
            $record = $query->one();
        }

        $record->setAttribute('handled', 1);
        $record->save();
        return;
    }

    /**
     * @param NotFoundModel $model
     * @return RedirectModel|false
     */
    private function getMatchingRedirect(NotFoundModel $model): RedirectRecord|array|bool
    {
        Craft::debug("Check if our 404 is matched to a redirect", SeoFields::class);
        $parsedUrl = parse_url($model->urlPath);

        $redirect = RedirectRecord::find();

        $redirect->where(['and',
            Db::parseParam('pattern', $model->urlPath, '='),
            Db::parseParam('sourceMatch', 'path', '='),
        ]);
        $redirect->andWhere(Db::parseParam('siteId', [null, $model->siteId], 'in'));

        if ($redirect->one()) {
            return $redirect->one();
        }

        $redirect->where(['and',
            Db::parseParam('pattern', $parsedUrl['path'], '='),
            Db::parseParam('sourceMatch', 'pathWithoutParams', '='),
        ]);

        if ($redirect->one()) {
            return $redirect->one();
        }

        // get all regex redirects and loop through them to get the match in PHP
        $regexRedirects = $this->getAllRegexRedirects($model);
        foreach ($regexRedirects as $regexRedirect) {
            $pattern = '`' . $regexRedirect->pattern . '`i';
            if (preg_match(
                    $pattern,
                    $model->urlPath
                ) === 1) {
                $parsedUrl = preg_replace($pattern, $regexRedirect->redirect, $model->fullUrl);
                return ["record" => $regexRedirect, "url" => $parsedUrl];
            }
        }

        return false;
    }


    private function saveNotFound(NotFoundModel $model)
    {
        $record = false;
        if (isset($model->id)) {
            $record = NotFoundRecord::findOne([
                'id' => $model->id,
            ]);
        }

        if (!$record) {
            $record = new NotFoundRecord();
            $record->uid = StringHelper::UUID();
        }

        $record->setAttribute('siteId', $model->siteId);
        $record->setAttribute('fullUrl', $model->fullUrl);
        $record->setAttribute('urlPath', $model->urlPath);
        $record->setAttribute('referrer', $model->referrer);
        $record->setAttribute('counter', $model->counter);
        $record->setAttribute('redirect', $model->redirect);
        $record->setAttribute('handled', $model->handled);
        $record->setAttribute('dateLastHit', $model->dateLastHit);
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

    public function deleteAll()
    {
        $records = NotFoundRecord::find();
        foreach ($records->all() as $record) {
            $record->delete();
        }
        return true;
    }

    private function shouldWeCleanupRedirects()
    {
        $total = NotFoundRecord::find()->count();
        $max = SeoFields::getInstance()->getSettings()->notFoundLimit;
        if ($total <= $max) {
            return;
        }

        $limit = $total - $max;
        $toDelete = NotFoundRecord::find();
        $toDelete->limit($limit);
        $toDelete->orderBy("dateCreated ASC");
        foreach ($toDelete->all() as $record) {
            $this->deletetById($record->id);
        }
    }

    private function getAllRegexRedirects(NotFoundModel $model): array
    {
        $query = RedirectRecord::find();
        $query->where(Db::parseParam('matchType', 'regexMatch'));
        $query->andWhere(Db::parseParam('siteId', [$model->siteId, null], 'IN'));
        return $query->all();
    }
}
