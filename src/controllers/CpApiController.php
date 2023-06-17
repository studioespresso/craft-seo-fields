<?php

namespace studioespresso\seofields\controllers;

use Craft;
use craft\helpers\Db;
use craft\helpers\Template;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use studioespresso\seofields\models\SeoDefaultsModel;
use studioespresso\seofields\records\DefaultsRecord;
use studioespresso\seofields\records\NotFoundRecord;
use studioespresso\seofields\SeoFields;
use yii\helpers\StringHelper;
use yii\web\NotFoundHttpException;
use function React\Promise\all;

class CpApiController extends Controller
{

    const NOT_FOUND_BASE = "seo-fields/cp-api/not-found";

    /**
     * @param null $siteHandle
     * @return \yii\web\Response
     */
    public function actionNotFound()
    {

        $sort = $this->request->getQueryParam('sort');
        if(!$sort) {
            $sort = "counter|desc";
        };

        $page = $this->request->getQueryParam('page', 1);
        list($key, $direction) = explode("|", $sort);

        $total = NotFoundRecord::find()->count();
        $limit = 20;

        $query = NotFoundRecord::find();

        $test = NotFoundRecord::find();

        $site  = $this->request->getQueryParam('site');

        if ($site) {
            $site = Craft::$app->getSites()->getSiteByHandle($site);
            $query->where(Db::parseParam('siteId', $site->id));
        }
        if($total> $limit) {
            $query->offset($page * 10);
            $query->limit($limit);
        }
        $query->orderBy($key . " " . $direction);
        $rows = [];

        foreach ($query->all() as $row) {
            $row = [
                'id' => $row->id,
                'title' => $row->urlPath,
                'hits' => $row->counter,
                'site' => $row->siteId,
                'hasRedirect' => $row->handled ? $row->handled : $row,
            ];

            $rows[] = $row;
        }
        $nextPageUrl = self::NOT_FOUND_BASE;
        $prevPageUrl = self::NOT_FOUND_BASE;
        $lastPage = (int)ceil($total / $limit);
        $to = $page === $lastPage ? $total : ($total < $limit ? $total : ($page * $limit));

        return $this->asJson([
            'pagination' => [
                'total' => (int)$total,
                'per_page' => (int)$limit,
                'current_page' => (int)$page,
                'last_page' => (int)$lastPage,
                'next_page_url' => $nextPageUrl,
                'prev_page_url' => $prevPageUrl,
                'from' => (int)(($page*$limit)-$limit)+1,
                'to' => (int)$to,
            ],
            'data' => $rows
        ]);

    }


}
