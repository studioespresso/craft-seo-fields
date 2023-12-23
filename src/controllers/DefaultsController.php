<?php

namespace studioespresso\seofields\controllers;

use Craft;
use craft\helpers\Cp;
use craft\helpers\UrlHelper;
use craft\web\Controller;
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
            $this->redirect(UrlHelper::cpUrl("seo-fields/defaults/settings", $params));
        } elseif ($currentUser->can('seo-fields:notfound')) {
            $this->redirect(UrlHelper::cpUrl("seo-fields/not-found", $params));
        } elseif ($currentUser->can('seo-fields:redirects')) {
            $this->redirect(UrlHelper::cpUrl("seo-fields/redirects", $params));
        } elseif ($currentUser->can('seo-fields:robots')) {
            $this->redirect(UrlHelper::cpUrl("seo-fields/robots", $params));
        } elseif ($currentUser->can('seo-fields:sitemap')) {
            $this->redirect(UrlHelper::cpUrl("seo-fields/sitemap", $params));
        }
    }

    public function actionSettings()
    {
        $site = $this->request->getRequiredQueryParam('site');
        $currentSite = Craft::$app->getSites()->getSiteByHandle($site);

        $sites = Craft::$app->getSites()->getEditableSites();
        Craft::$app->sites->setCurrentSite($site);


        return $this->asCpScreen()
            ->title(Craft::t('seo-fields', 'SEO Fields'))
            ->selectedSubnavItem('defaults')
            ->crumbs([
                [
                    'label' => $currentSite->name,
                    'menu' => [
                        'label' => Craft::t('site', 'Select site'),
                        'items' => Cp::siteMenuItems($sites, $currentSite),
                    ]
                ]
            ])
            ->action('seo-fields/defaults/save')
            ->contentTemplate('seo-fields/_defaults/_content', [
                'data' => SeoFields::$plugin->defaultsService->getDataBySite($currentSite),
                'site' => $currentSite,
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
