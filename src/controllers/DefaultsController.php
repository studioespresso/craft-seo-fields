<?php

namespace studioespresso\seofields\controllers;

use Craft;
use craft\web\Controller;
use studioespresso\seofields\models\SeoDefaultsModel;
use studioespresso\seofields\records\DefaultsRecord;
use studioespresso\seofields\SeoFields;

class DefaultsController extends Controller
{

    public function actionIndex()
    {
        $primarySite = Craft::$app->sites->getPrimarySite();
        $this->redirect("seo-fields/defaults/$primarySite->handle");
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
        $data['defaultImage'] = Craft::$app->getRequest()->getBodyParam('defaultImage');

        $defaultsModel = SeoFields::$plugin->defaultsService->getDataBySiteId($data['siteId']);
        $defaultsModel->setAttributes($data);
        SeoFields::$plugin->defaultsService->saveDefaults($defaultsModel, $data['siteId']);
    }
}
