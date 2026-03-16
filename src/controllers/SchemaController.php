<?php

namespace studioespresso\seofields\controllers;

use Craft;
use craft\models\Site;
use craft\web\Controller;
use studioespresso\seofields\models\SeoDefaultsModel;
use studioespresso\seofields\SeoFields;

class SchemaController extends Controller
{
    protected array|bool|int $allowAnonymous = false;

    public Site|null $site = null;

    public function init(): void
    {
        parent::init();
        $this->requirePermission('seo-fields:schema');

        if (Craft::$app->getRequest()->getQueryParam('site')) {
            $this->site = Craft::$app->getSites()->getSiteByHandle(Craft::$app->getRequest()->getQueryParam('site'));
        }
        if (!$this->site) {
            $this->site = Craft::$app->getSites()->getPrimarySite();
        }
    }

    public function actionIndex()
    {
        $data = SeoFields::$plugin->defaultsService->getDataBySiteId($this->site->id);
        $sections = Craft::$app->getEntries()->getAllSections();

        return $this->renderTemplate('seo-fields/_schema', [
            'data' => $data,
            'sitemapPerSite' => SeoFields::$plugin->getSettings()->sitemapPerSite,
            'sections' => $sections,
            'options' => SeoFields::getInstance()->schemaService->getDefaultOptions(),
            'siteEntityOptions' => SeoFields::getInstance()->schemaService->getSiteEntityOptions(),
            'selectedSite' => $this->site,
        ]);
    }

    public function actionSave()
    {
        $this->requirePostRequest();

        $data = [];
        if (Craft::$app->getRequest()->getBodyParam('id')) {
            $model = SeoFields::$plugin->defaultsService->getDataById(Craft::$app->getRequest()->getBodyParam('id'));
        } else {
            $model = new SeoDefaultsModel();
        }

        $data['schema'] = Craft::$app->getRequest()->getBodyParam('data');
        $data['siteId'] = Craft::$app->getRequest()->getBodyParam('siteId', Craft::$app->getSites()->getPrimarySite()->id);
        $data['siteEntity'] = Craft::$app->getRequest()->getBodyParam('siteEntity');
        $data['organizationName'] = Craft::$app->getRequest()->getBodyParam('organizationName');
        $data['organizationLogo'] = Craft::$app->getRequest()->getBodyParam('organizationLogo');

        $sameAsRows = Craft::$app->getRequest()->getBodyParam('sameAs');
        if (is_array($sameAsRows)) {
            $data['sameAs'] = array_values(array_filter(array_map(fn($row) => $row['url'] ?? null, $sameAsRows)));
        } else {
            $data['sameAs'] = [];
        }

        $model->setAttributes($data);
        SeoFields::$plugin->defaultsService->saveDefaults($model, $data['siteId']);
        SeoFields::$plugin->sitemapService->clearCaches();

        Craft::$app->getSession()->setNotice(Craft::t('seo-fields', 'Schema settings saved.'));
        return $this->redirectToPostedUrl();
    }
}
