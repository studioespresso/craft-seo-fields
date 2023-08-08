<?php

namespace studioespresso\seofields\controllers;

use Craft;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use studioespresso\seofields\models\SeoDefaultsModel;
use studioespresso\seofields\records\DefaultsRecord;
use studioespresso\seofields\SeoFields;

class DefaultsController extends Controller
{

    public function actionIndex()
    {
        $params = Craft::$app->getRequest()->getQueryParams();
        unset($params['p']);
        $currentUser = Craft::$app->getUser()->getIdentity();
        $primarySite = Craft::$app->sites->getPrimarySite();
        if ($currentUser->can('seo-fields:default')) {
            $this->redirect(UrlHelper::cpUrl("seo-fields/defaults/$primarySite->handle", $params));

        } elseif ($currentUser->can('seo-fields:notfound')) {
            $this->redirect(UrlHelper::cpUrl("seo-fields/not-found/$primarySite->handle", $params));
        } elseif ($currentUser->can('seo-fields:redirects')) {
            $this->redirect(UrlHelper::cpUrl("seo-fields/redirects/$primarySite->handle", $params));
        } elseif ($currentUser->can('seo-fields:robots')) {
            $this->redirect(UrlHelper::cpUrl("seo-fields/robots/$primarySite->handle", $params));
        } elseif ($currentUser->can('seo-fields:sitemap')) {
            $this->redirect(UrlHelper::cpUrl("seo-fields/sitemap/$primarySite->handle", $params));
        }
    }

    public function actionSettings($siteHandle = null)
    {
        $currentSite = Craft::$app->sites->getSiteByHandle($siteHandle);
        Craft::$app->sites->setCurrentSite($currentSite);
        $data = SeoFields::$plugin->defaultsService->getDataBySite($currentSite);
        return $this->renderTemplate('seo-fields/_defaults', [
            'data' => $data
        ]);

    }

    public function actionSave()
    {
        $data = [];
        $data['siteId'] = Craft::$app->getRequest()->getBodyParam('siteId');
        $data['titleSeperator'] = Craft::$app->getRequest()->getBodyParam('titleSeperator');
        $data['defaultSiteTitle'] = Craft::$app->getRequest()->getBodyParam('defaultSiteTitle');
        $data['defaultMetaDescription'] = Craft::$app->getRequest()->getBodyParam('defaultMetaDescription');
        $data['defaultImage'] = Craft::$app->getRequest()->getBodyParam('defaultImage');

        $defaultsModel = SeoFields::$plugin->defaultsService->getDataBySiteId($data['siteId']);
        $defaultsModel->setAttributes($data);
        SeoFields::$plugin->defaultsService->saveDefaults($defaultsModel, $data['siteId']);
    }
}
