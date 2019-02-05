<?php

namespace studioespresso\seofields\controllers;

use Craft;
use craft\helpers\Template;
use craft\web\Controller;
use studioespresso\seofields\models\SeoDefaultsModel;
use studioespresso\seofields\records\DefaultsRecord;
use studioespresso\seofields\SeoFields;
use yii\helpers\StringHelper;

class SitemapController extends Controller
{
    public $allowAnonymous = ['actionRender'];

    public function actionIndex()
    {
        $primarySite = Craft::$app->sites->getPrimarySite();
        $this->redirect("seo-fields/sitemap/$primarySite->handle");
    }

    public function actionSettings($siteHandle = null)
    {
        $currentSite = Craft::$app->sites->getSiteByHandle($siteHandle);
        Craft::$app->sites->setCurrentSite($currentSite);
        $data = SeoFields::$plugin->defaultsService->getDataBySite($currentSite);
        $sections = Craft::$app->getSections()->getAllSections();
        return $this->renderTemplate('seo-fields/_sitemap', [
            'data' => $data,
        ]);
    }

    public function actionSave()
    {
        dd(Craft::$app->getRequest()->getBodyParams());
    }
}
