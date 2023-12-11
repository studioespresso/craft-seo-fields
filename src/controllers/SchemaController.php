<?php

namespace studioespresso\seofields\controllers;

use Craft;
use craft\db\Query;
use craft\helpers\Db;
use craft\records\Section_SiteSettings;
use craft\web\Controller;
use studioespresso\seofields\models\SeoDefaultsModel;
use studioespresso\seofields\SeoFields;
use yii\web\NotFoundHttpException;

class SchemaController extends Controller
{
    protected array|bool|int $allowAnonymous = false;

    public function actionIndex()
    {
        $primarySite = Craft::$app->getSites()->getPrimarySite();
        $data = SeoFields::$plugin->defaultsService->getDataBySiteHandle($primarySite->handle);
        $sections = Craft::$app->getSections()->getAllSections();

        return $this->renderTemplate('seo-fields/_schema', [
            'data' => $data,
            'sitemapPerSite' => SeoFields::$plugin->getSettings()->sitemapPerSite,
            'sections' => $sections,
            'selectedSite' => $primarySite,
        ]);
    }



}
