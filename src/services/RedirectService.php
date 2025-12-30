<?php

namespace studioespresso\seofields\services;

use Craft;
use craft\base\Component;
use craft\base\Element;
use craft\helpers\App;
use craft\helpers\DateTimeHelper;
use craft\helpers\ElementHelper;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use studioespresso\seofields\models\RedirectModel;
use studioespresso\seofields\records\RedirectRecord;
use studioespresso\seofields\SeoFields;
use yii\base\ExitException;

/**
 * @author    Studio Espresso
 * @package   SeoFields
 * @since     1.0.0
 */
class RedirectService extends Component
{
    public $oldUris = [];


    public function trackElementUris(Element $element)
    {
        if (empty($this->oldUris[$element->id])) {
            $this->oldUris[$element->id] = $this->getElementUrls($element);
        }
    }

    public function handleUriChange(Element $element)
    {
        if (empty($this->oldUris[$element->id])) {
            return;
        }

        foreach ($this->oldUris[$element->id] as $siteId => $oldUri) {
            $newUri = Craft::$app->getElements()->getElementUriForSite($element->id, $siteId);
            // It's possible that the element has no URI in this site, so we skip it (https://github.com/studioespresso/craft-seo-fields/issues/116)
            if (!$newUri) {
                continue;
            }

            // Should be keep trailing slashes into account here (or when the old one have those as well)
            if (Craft::$app->config->general->addTrailingSlashesToUrls) {
                $oldUri = rtrim($oldUri, '/') . '/';
                $newUri = rtrim($newUri, '/') . '/';
            }

            if ($newUri !== $oldUri) {
                // Let's add a redirect
                $oldUrl = parse_url(UrlHelper::siteUrl($oldUri, null, null, $siteId));
                $newUrl = UrlHelper::siteUrl($newUri, null, null, $siteId);
                $redirect = new RedirectModel();
                $redirect->pattern = $oldUrl['path'];
                $redirect->sourceMatch = 'path';
                $redirect->redirect = $newUrl;
                $redirect->matchType = 'exact';
                $redirect->siteId = $siteId;
                $redirect->method = 301;
                $this->saveRedirect($redirect);
            }
        }
    }


    public function handleRedirect(RedirectRecord|array $redirect)
    {
        if (is_array($redirect)) {
            $record = $redirect['record'];
            $model = new RedirectModel($record->getAttributes());
        } else {
            $model = new RedirectModel($redirect->getAttributes());
        }
        Craft::debug("Found a redirect for this 404, redirecting", SeoFields::class);
        $this->updateOnRedirect($model);
        $this->redirect($redirect);

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
            $query->orWhere(['like', 'redirect', $searchParam]);
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

        if ($model->sourceMatch !== 'url') {
            if (substr($model->pattern, 0, 1) == '/') {
                $record->setAttribute('pattern', $model->pattern);
            } else {
                $record->setAttribute('pattern', "/" . $model->pattern);
            }
        } else {
            $record->setAttribute('pattern', $model->pattern);
        }
//        dd($record->getAttributes());
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

    public function deleteAll()
    {
        $records = RedirectRecord::find();
        foreach ($records->all() as $record) {
            $record->delete();
        }
        return true;
    }

    public function deleteRedirectById($id)
    {
        $record = RedirectRecord::findOne(['id' => $id]);
        if ($record->delete()) {
            return true;
        }
    }

    public function import($data, $settings)
    {
        App::maxPowerCaptain();
        $patternCol = $settings['patternCol'];
        $redirectCol = $settings['redirectCol'];
        $validRedirects = [];
        $invalidRedirects = [];

        foreach ($data as $row) {
            $row = array_values($row);
            $pattern = $row[$patternCol];
            $redirect = $row[$redirectCol];
            if ($pattern === $redirect) {
                continue;
            } elseif (substr($redirect, 0, 1) != "/") {
                $invalidRedirects[] = $row;
                continue;
            }
            $validRedirects[] = $row;
        }

        foreach ($validRedirects as $row) {
            $model = new RedirectModel();
            $model->pattern = $row[$patternCol];
            $model->redirect = $row[$redirectCol];
            $model->matchType = 'exact';
            $model->sourceMatch = 'path';
            $model->siteId = $settings['siteId'];
            $model->method = $settings['method'];
            if (!$model->validate()) {
                Craft::error(Json::encode($model->getErrors()));
            }
            $this->saveRedirect($model);
        }

        return [
            'imported' => $validRedirects,
            'invalid' => $invalidRedirects,
        ];
    }

    private function redirect(RedirectModel|RedirectRecord|array $redirect)
    {
        try {
            if (is_array($redirect)) {
                $url = $redirect['url'];
                $method = $redirect['record']->method;
            } else {
                $method = $redirect->method;
                if ($redirect->siteId) {
                    $url = UrlHelper::siteUrl($redirect->redirect, null, null, $redirect->siteId);
                } else {
                    $url = $redirect->redirect;
                }
            }

            $response = Craft::$app->response;
            if (Craft::$app->getRequest()->getQueryStringWithoutPath()) {
                $response->redirect($url . "?" . Craft::$app->getRequest()->getQueryStringWithoutPath(), $method)->send();
            }

            $response->redirect($url, $method)->send();
        } catch (\Exception $e) {
        }
        return;
    }

    private function getElementUrls(Element $element)
    {
        $uris = [];
        if (!ElementHelper::isDraftOrRevision($element) && $element->id) {
            foreach (Craft::$app->getSites()->getAllSites(true) as $site) {
                $uri = Craft::$app->getElements()->getElementUriForSite($element->id, $site->id);
                if ($uri) {
                    $uris[$site->id] = $uri;
                }
            }
        }

        return $uris;
    }
}
