<?php

namespace studioespresso\seofields\controllers;

use Craft;
use craft\web\Controller;
use studioespresso\seofields\models\SeoDefaultsModel;
use studioespresso\seofields\SeoFields;

class SchemaController extends Controller
{
    protected array|bool|int $allowAnonymous = false;

    public function actionIndex()
    {
        $primarySite = Craft::$app->getSites()->getPrimarySite();
        $data = SeoFields::$plugin->defaultsService->getDataBySiteHandle($primarySite->handle);
        $sections = Craft::$app->getEntries()->getAllSections();

        return $this->renderTemplate('seo-fields/_schema', [
            'data' => $data,
            'sitemapPerSite' => SeoFields::$plugin->getSettings()->sitemapPerSite,
            'sections' => $sections,
            'options' => SeoFields::getInstance()->schemaService->getDefaultOptions(),
            'selectedSite' => $primarySite,
        ]);
    }

    public function actionSave()
    {
        $data = [];
        if (Craft::$app->getRequest()->getBodyParam('id')) {
            $model = SeoFields::$plugin->defaultsService->getDataById(Craft::$app->getRequest()->getBodyParam('id'));
        } else {
            $model = new SeoDefaultsModel();
        }

        $data['schema'] = Craft::$app->getRequest()->getBodyParam('data');
        $data['siteId'] = Craft::$app->getRequest()->getBodyParam('siteId', Craft::$app->getSites()->getPrimarySite()->id);
        $model->setAttributes($data);
        SeoFields::$plugin->defaultsService->saveDefaults($model, $data['siteId']);
        SeoFields::$plugin->sitemapSerivce->clearCaches();
    }
}
