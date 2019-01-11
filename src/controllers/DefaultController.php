<?php

namespace studioespresso\seofields\controllers;

use Craft;
use craft\web\Controller;
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
        $sites = Craft::$app->sites->getEditableSites();
        $currentSite = Craft::$app->sites->getSiteByHandle($siteHandle);
        Craft::$app->sites->setCurrentSite($currentSite);
        $data = SeoFields::$plugin->defaultsService->getDefaultsBySite($currentSite);
        return $this->renderTemplate('seo-fields/_defaults', [
            'sites' => $sites,
            'data' => $data
        ]);

    }
}
