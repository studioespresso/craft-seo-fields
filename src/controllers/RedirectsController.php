<?php

namespace studioespresso\seofields\controllers;

use Craft;
use craft\helpers\App;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use League\Csv\Reader;
use studioespresso\seofields\models\RedirectModel;
use studioespresso\seofields\SeoFields;
use yii\web\UploadedFile;

class RedirectsController extends Controller
{
    public const IMPORT_FILE = 'seofields_redirects_import.csv';

    public function actionIndex()
    {
        $searchParam = Craft::$app->getRequest()->getParam('search');
        $redirects = SeoFields::getInstance()->redirectService->getAllRedirects($searchParam);
        return $this->renderTemplate('seo-fields/_redirect/_index', ['redirects' => $redirects]);
    }

    public function actionAdd()
    {
        return $this->renderTemplate('seo-fields/_redirect/_entry', [
            'pattern' => Craft::$app->getRequest()->getParam('pattern') ?? null,
            'record' => Craft::$app->getRequest()->getParam('record') ?? null,
            'sites' => $this->getSitesMenu(),
        ]);
    }

    public function actionEdit($id)
    {
        $redirect = SeoFields::getInstance()->redirectService->getRedirectById($id);
        return $this->renderTemplate('seo-fields/_redirect/_entry', [
            'data' => $redirect,
            'sites' => $this->getSitesMenu(),
        ]);
    }

    public function actionSave()
    {
        $id = $this->request->getBodyParam('redirectId');
        $record = $this->request->getBodyParam('record');
        if ($id) {
            $model = SeoFields::getInstance()->redirectService->getRedirectById($id);
        } else {
            $model = new RedirectModel();
        }

        $model->setAttributes(Craft::$app->getRequest()->getBodyParam('fields'));

        if ($model->validate()) {
            $saved = SeoFields::getInstance()->redirectService->saveRedirect($model);
            if ($saved) {
                if ($record) {
                    SeoFields::getInstance()->notFoundService->markAsHandled($record);
                }
                Craft::$app->getSession()->setNotice(Craft::t('seo-fields', 'Redirect saved'));
                $this->redirectToPostedUrl();
            }
        }

        Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t save redirect.'));
        return $this->renderTemplate('seo-fields/_redirect/_entry', [
            'data' => $model,
            'sites' => $this->getSitesMenu(),
        ]);
    }

    public function actionUpload()
    {
        $this->requirePostRequest();

        // If your CSV document was created or is read on a Macintosh computer,
        // add the following lines before using the library to help PHP detect line ending in Mac OS X
        if (!ini_get('auto_detect_line_endings')) {
            ini_set('auto_detect_line_endings', '1');
        }

        $file = UploadedFile::getInstanceByName('file');

        if ($file !== null) {
            $filename = self::IMPORT_FILE;
            $filePath = Craft::$app->getPath()->getTempPath() . DIRECTORY_SEPARATOR . $filename;
            $file->saveAs($filePath, false);
            $csv = Reader::createFromPath($file->tempName);
            $headers = $csv->fetchOne(0);
            Craft::info(print_r($headers, true), __METHOD__);
            $variables['headers'] = $headers;
            $variables['filename'] = $filePath;
        }

        $this->redirect(UrlHelper::cpUrl('seo-fields/redirects/import'));
    }

    public function actionImport()
    {
        $filename = self::IMPORT_FILE;
        $filePath = Craft::$app->getPath()->getTempPath() . DIRECTORY_SEPARATOR . $filename;
        if (!file_exists($filePath)) {
            return $this->redirect(UrlHelper::cpUrl('seo-fields/redirects'));
        }
        $csv = Reader::createFromPath($filePath);
        $headers = $csv->fetchOne(0);
        $variables['headers'] = $headers;
        $variables['filename'] = $filePath;
        $variables['sites'] = $this->getSitesMenu();

        $this->renderTemplate('seo-fields/_redirect/_import', $variables);
    }

    public function actionRunImport()
    {
        $request = Craft::$app->getRequest();
        $data = $request->getBodyParam('fields');
        if (!$data['pattern'] || $data['redirect'] || $data['method']) {
        }

        App::maxPowerCaptain();
        $settings = [
            'patternCol' => $data['pattern'],
            'redirectCol' => $data['redirect'],
            'siteId' => $data['siteId'],
            'method' => $data['method'],
        ];

        $filename = self::IMPORT_FILE;
        $filePath = Craft::$app->getPath()->getTempPath() . DIRECTORY_SEPARATOR . $filename;
        $reader = Reader::createFromPath($filePath);
        //$headers = $reader->fetchOne(0);
        $reader->setHeaderOffset(1);

        $headers = $this->getHeaders($reader);
        $rows = $this->getRows($reader);
        $variables['headers'] = $headers;
        $variables['filename'] = $filePath;

        $results = SeoFields::getInstance()->redirectService->import($rows, $settings);
        return $this->renderTemplate('seo-fields/_redirect/_import_results', $results);
    }

    public function actionDelete()
    {
        $id = $this->request->getBodyParam('id');
        if (SeoFields::getInstance()->redirectService->deleteRedirectById($id)) {
            Craft::$app->getSession()->setNotice(Craft::t('seo-fields', 'Redirect removed'));
            return $this->asJson(['success' => true]);
        }
    }

    private function getSitesMenu()
    {
        $sites = [
            0 => Craft::t('seo-fields', 'All Sites'),
        ];

        if (Craft::$app->getIsMultiSite()) {
            $editableSites = Craft::$app->getSites()->getEditableSiteIds();
            foreach (Craft::$app->getSites()->getAllGroups() as $group) {
                $groupSites = Craft::$app->getSites()->getSitesByGroupId($group->id);
                $sites[$group->name]
                    = ['optgroup' => $group->name];
                foreach ($groupSites as $groupSite) {
                    if (in_array($groupSite->id, $editableSites, false)) {
                        $sites[$groupSite->id] = $groupSite->name;
                    }
                }
            }
        }
        return $sites;
    }

    private function getHeaders($reader)
    {
        // Support for league/csv v8 with a header
        try {
            return $reader->fetchOne(0);
        } catch (\Throwable $e) {
        }

        try {
            $reader->setHeaderOffset(0);
            return $reader->getHeader();
        } catch (\Throwable $e) {
        }
    }
    private function getRows(Reader $reader)
    {
        try {
            return $reader->fetchAll();
        } catch (\Throwable $e) {
        }

        try {
            return $reader->getIterator();
        } catch (\Throwable $e) {
        }
    }
}
