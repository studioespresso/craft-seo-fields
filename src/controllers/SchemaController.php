<?php

namespace studioespresso\seofields\controllers;

use Craft;
use craft\helpers\Cp;
use craft\models\Site;
use craft\web\Controller;
use craft\web\Response;
use studioespresso\seofields\models\SeoDefaultsModel;
use studioespresso\seofields\SeoFields;

class SchemaController extends Controller
{
    protected array|bool|int $allowAnonymous = false;

    public Site|null $site = null;

    public function init(): void
    {
        if (Craft::$app->getRequest()->getQueryParam('site')) {
            $this->site = Craft::$app->getSites()->getSiteByHandle(Craft::$app->getRequest()->getQueryParam('site'));
        }
        if (!$this->site) {
            $this->site = Craft::$app->getSites()->getPrimarySite();
        }
        parent::init();
    }

    public function actionIndex()
    {
        $this->requirePermission('seo-fields:schema');

        $data = SeoFields::$plugin->defaultsService->getDataBySiteId($this->site->id);
        $sections = Craft::$app->getEntries()->getAllSections();
        $sites = Craft::$app->getSites()->getEditableSites();

        $siteCrumb = ['label' => $this->site->name];

        if (Craft::$app->getIsMultiSite()) {
            $siteCrumb['menu'] = [
                'label' => Craft::t('site', 'Select site'),
                'items' => Cp::siteMenuItems($sites, $this->site),
            ];
        }

        return $this->asCpScreen()
            ->title(Craft::t('seo-fields', 'Schema.org'))
            ->selectedSubnavItem('schema')
            ->crumbs([
                ['label' => Craft::t('seo-fields', 'SEO Fields'), 'url' => 'seo-fields'],
                $siteCrumb,
            ])
            ->action('seo-fields/schema/save')
            ->redirectUrl('seo-fields/schema?site=' . $this->site->handle)
            ->addAltAction(Craft::t('seo-fields', 'Save'), [
                'shortcut' => true,
                'retainScroll' => true,
                'redirect' => 'seo-fields/schema?site=' . $this->site->handle,
            ])
            ->contentTemplate('seo-fields/_schema/_content', [
                'data' => $data,
                'sections' => $sections,
                'options' => SeoFields::getInstance()->schemaService->getDefaultOptions(),
                'siteEntityOptions' => SeoFields::getInstance()->schemaService->getSiteEntityOptions(),
                'site' => $this->site,
            ]);
    }

    public function actionSave()
    {
        $this->requirePostRequest();

        $data = [];
        $siteId = Craft::$app->getRequest()->getBodyParam('siteId', Craft::$app->getSites()->getPrimarySite()->id);

        $model = SeoFields::$plugin->defaultsService->getDataBySiteId($siteId);
        if (!$model) {
            $model = new SeoDefaultsModel();
        }

        $data['schema'] = Craft::$app->getRequest()->getBodyParam('data');
        $data['siteId'] = $siteId;
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
