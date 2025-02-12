<?php

namespace studioespresso\seofields\controllers;

use Craft;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\UrlHelper;
use craft\i18n\Locale;
use craft\web\Controller;
use studioespresso\seofields\records\NotFoundRecord;
use studioespresso\seofields\records\RedirectRecord;

class CpApiController extends Controller
{
    public const NOT_FOUND_BASE = "seo-fields/cp-api/not-found";
    public const REDIRECT_BASE = "seo-fields/cp-api/redirect";

    public function actionNotFound()
    {
        $sort = $this->request->getQueryParam('sort');
        $search = $this->request->getQueryParam('search');
        if (!$sort) {
            $sort = "counter|desc";
        };

        $page = $this->request->getQueryParam('page', 1);
        list($key, $direction) = explode("|", $sort);

        $limit = 20;
        $offset = ($page - 1) * $limit;

        $query = NotFoundRecord::find();
        $query->limit($limit);
        $query->offset($offset);

        $site = $this->request->getQueryParam('site');
        if ($site) {
            $site = Craft::$app->getSites()->getSiteByHandle($site);
            $query->orWhere(Db::parseParam('siteId', $site->id));
        }

        if ($search) {
            $query->andWhere([
                'or',
                "urlPath LIKE '%{$search}%'",
                "fullUrl LIKE '%{$search}%'",
            ]);
        }

        $query->orderBy($key . " " . $direction);

        $total = clone  $query;
        $total = $total->count();

        $rows = [];
        $formatter = Craft::$app->getFormatter();

        foreach ($query->all() as $row) {
            $lastHit = DateTimeHelper::toDateTime($row->getAttribute('dateLastHit'));

            $row = [
                'id' => $row->getAttribute('id'),
                'title' => $row->getAttribute('urlPath'),
                'urlPath' => $row->getAttribute('urlPath'),
                'hits' => $row->getAttribute('counter'),
                'siteId' => $row->getAttribute('siteId'),
                'lastHit' => $formatter->asDatetime($lastHit, Locale::LENGTH_SHORT),
                'site' => Craft::$app->getSites()->getSiteById($row->getAttribute('siteId'))->name,
                'hasRedirect' => $row->getAttribute('handled') ? $row->getAttribute('handled') : $row,
            ];

            $rows[] = $row;
        }

        $from = ($page - 1) * $limit + 1;
        $lastPage = (int) ceil($total / $limit);
        $to = intval($page) === intval($lastPage) ? $total : ($page * $limit);

        return $this->asJson([
            'pagination' => [
                'total' => (int)$total,
                'per_page' => (int)$limit,
                'current_page' => (int)$page,
                'last_page' => (int)$lastPage,
                'next_page_url' => self::NOT_FOUND_BASE,
                'prev_page_url' => self::NOT_FOUND_BASE,
                'from' => (int)(($page * $limit) - $limit) + 1,
                'to' => (int)$to,
            ],
            'data' => $rows,
        ]);
    }

    public function actionRedirects()
    {
        $sort = $this->request->getQueryParam('sort');
        $search = $this->request->getQueryParam('search');
        if (!$sort) {
            $sort = [
                [
                    'field' => 'hits',
                    'sortField' => 'counter',
                    'direction' => 'desc',
                ]
            ];
        }

        $page = $this->request->getQueryParam('page', 1);

        $limit = 20;
        $offset = ($page - 1) * $limit;

        $query = RedirectRecord::find();
        $query->limit($limit);
        $query->offset($offset);

        $site = $this->request->getQueryParam('site');
        if ($site) {
            $site = Craft::$app->getSites()->getSiteByHandle($site);
            $query->orWhere(Db::parseParam('siteId', [$site->id, null], 'IN'));
        }

        if ($search) {
            $query->andWhere([
                'or',
                "pattern LIKE '%{$search}%'",
                "redirect LIKE '%{$search}%'",
            ]);
        }

        $query->orderBy($sort[0]['sortField'] . " " . $sort[0]['direction']);
        $total = clone  $query;
        $total = $total->count();

        $rows = [];
        $formatter = Craft::$app->getFormatter();

        $types = [
            'exact' => 'Exact match',
            'regexMatch' => 'Regex match',
        ];

        foreach ($query->all() as $row) {
            $lastHit = DateTimeHelper::toDateTime($row->getAttribute('dateLastHit'));
            $row = [
                'url' => UrlHelper::cpUrl("seo-fields/redirects/edit/{$row->getAttribute('id')}"),
                'id' => $row->getAttribute('id'),
                'title' => $row->getAttribute('pattern'),
                'redirect' => $row->getAttribute('redirect'),
                'counter' => $row->getAttribute('counter'),
                'site' => !$row->getAttribute('siteId') ? "All" : Craft::$app->getSites()->getSiteById($row->getAttribute('siteId'))->name,
                'lastHit' => $lastHit ? $formatter->asDatetime($lastHit, Locale::LENGTH_SHORT) : "",
                'method' => $row->getAttribute('method'),
                'matchType' => $types[$row->getAttribute('matchType')],
            ];

            $rows[] = $row;
        }

        $from = ($page - 1) * $limit + 1;
        $lastPage = (int) ceil($total / $limit);
        $to = (int)$page === $lastPage ? $total : ($page * $limit);

        return $this->asJson([
            'pagination' => [
                'total' => (int)$total,
                'per_page' => (int)$limit,
                'current_page' => (int)$page,
                'last_page' => (int)$lastPage,
                'next_page_url' => self::REDIRECT_BASE,
                'prev_page_url' => self::REDIRECT_BASE,
                'from' => (int)$from,
                'to' => (int)$to,
            ],
            'data' => $rows,
        ]);
    }
}
