<?php

namespace studioespresso\seofields\controllers;

use Craft;
use craft\web\Controller;
use studioespresso\seofields\models\SeoDefaultsModel;
use studioespresso\seofields\records\DefaultsRecord;
use studioespresso\seofields\SeoFields;

class DefaultController extends Controller
{

    public function actionIndex()
    {
        $primarySite = Craft::$app->sites->getPrimarySite();
        $this->redirect("seo-fields/defaults/$primarySite->handle");
    }

    public function actionDefaults($siteHandle = null)
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
        $data['titleSeperator'] = Craft::$app->getRequest()->getBodyParam('titleSeperator');
        $data['defaultSiteTitle'] = Craft::$app->getRequest()->getBodyParam('defaultSiteTitle');
        $data['siteId'] = Craft::$app->sites->currentSite->id;
        $defaultsModel = new SeoDefaultsModel();
        $defaultsModel->setAttributes($data);
        SeoFields::$plugin->defaultsService->saveDefaults($defaultsModel, Craft::$app->sites->currentSite->id);



    }
}
