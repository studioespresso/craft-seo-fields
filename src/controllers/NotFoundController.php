<?php

namespace studioespresso\seofields\controllers;

use Craft;
use craft\helpers\Cp;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use craft\web\Response;
use studioespresso\seofields\SeoFields;

class NotFoundController extends Controller
{
    public function actionIndex(): Response
    {
        $siteHandle = $this->request->getRequiredQueryParam('site');
        $currentSite = Craft::$app->getSites()->getSiteByHandle($siteHandle);
        $sites = Craft::$app->getSites()->getEditableSites();
        $crumbs[] = [
            'label' => $currentSite->name,
            'menu' => [
                'label' => Craft::t('site', 'Select site'),
                'items' => Cp::siteMenuItems($sites, $currentSite),
            ],
        ];

        return $this->asCpScreen()
            ->title(Craft::t('seo-fields', '404 Overview'))
            ->selectedSubnavItem('notfound')
            ->additionalButtonsTemplate('seo-fields/_notfound/_buttons')
            ->crumbs($crumbs)
            ->contentTemplate('seo-fields/_notfound/_content');
    }

    /**
     * @param $id
     * @throws \craft\errors\MissingComponentException
     */
    public function actionDelete()
    {
        $id = $this->request->getBodyParam('id');
        if (SeoFields::getInstance()->notFoundService->deletetById($id)) {
            Craft::$app->getSession()->setNotice(Craft::t('seo-fields', '404 removed'));
            return $this->asJson(['success' => true]);
        }
    }

    /**
     * @return \yii\web\Response
     * @todo Should this also take the current site into account?
     */
    public function actionClearAll()
    {
        SeoFields::getInstance()->notFoundService->deleteAll();
        return $this->redirect(UrlHelper::cpUrl('seo-fields/not-found'));
    }
}
