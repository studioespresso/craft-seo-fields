<?php

namespace studioespresso\seofields\controllers;

use Craft;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use studioespresso\seofields\SeoFields;

class NotFoundController extends Controller
{
    /**
     * @param null $siteHandle
     * @return \yii\web\Response
     */
    public function actionIndex($siteHandle = null)
    {
        if ($siteHandle) {
            $site = Craft::$app->getSites()->getSiteByHandle($siteHandle);
            Craft::$app->getSites()->setCurrentSite($site);
        }
        $handled = "all";
        if (Craft::$app->getRequest()->getParam('redirect')) {
            if (Craft::$app->getRequest()->getParam('redirect') === "handled") {
                $handled = 1;
            } elseif (Craft::$app->getRequest()->getParam('redirect') === "not-handled") {
                $handled = 0;
            }
        }
        return $this->renderTemplate('seo-fields/_notfound/_index');
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
