<?php

namespace studioespresso\seofields\controllers;

use Craft;
use craft\helpers\App;
use craft\helpers\Cp;
use craft\helpers\DateTimeHelper;
use craft\helpers\UrlHelper;
use craft\models\Site;
use craft\services\Path;
use craft\web\Controller;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Writer\XLSX\Writer;
use studioespresso\seofields\models\RedirectModel;
use studioespresso\seofields\records\RedirectRecord;
use studioespresso\seofields\SeoFields;
use yii\web\UploadedFile;

class RedirectsController extends Controller
{
    public const IMPORT_FILE = 'seofields_redirects_import.csv';

    public Site|null $site = null;

    public function init(): void
    {
        if (Craft::$app->getRequest()->getQueryParam('site')) {
            $this->site = Craft::$app->getSites()->getSiteByHandle(Craft::$app->getRequest()->getQueryParam('site'));
        } else {
            $this->site = Craft::$app->getSites()->getPrimarySite();
        }
        parent::init();
    }

    public function actionIndex()
    {
        $searchParam = Craft::$app->getRequest()->getParam('search');
        $redirects = SeoFields::getInstance()->redirectService->getAllRedirects($searchParam);

        $sites = Craft::$app->getSites()->getEditableSites();

        $crumbs = ['label' => $this->site->name, ];
        if (Craft::$app->getIsMultiSite()) {
            $crumbs['menu'] = [
                'label' => Craft::t('site', 'Select site'),
                'items' => Cp::siteMenuItems($sites, $this->site),
            ];
        }

        return $this->asCpScreen()
            ->selectedSubnavItem('redirects')
            ->additionalButtonsTemplate('seo-fields/_redirect/_buttons')
            ->title(Craft::t('seo-fields', 'Redirects'))
            ->crumbs([$crumbs])
            ->contentTemplate('seo-fields/_redirect/_content');
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

        $file = UploadedFile::getInstanceByName('file');

        if ($file !== null) {
            $filename = self::IMPORT_FILE;
            $filePath = Craft::$app->getPath()->getTempPath() . DIRECTORY_SEPARATOR . $filename;
            $file->saveAs($filePath, false);
        }

        $this->redirect(UrlHelper::cpUrl('seo-fields/redirects/import'));
    }

    public function actionExport()
    {
        $site = $this->request->getQueryParam('site', null);
        /** @var Path $pathService */
        $pathService = Craft::$app->getPath();
        $now = DateTimeHelper::now();
        $path = $pathService->getTempPath() . "/redirect-{$now->format('Y-m-d h:i:s')}.xlsx";

        $writer = new Writer();
        $writer->openToFile($path);

        $headerRow = Row::fromValues(["Old url", "Redirected to", "Type", "Site Name", "Last hit on", "Total hits"]);

        $writer->addRow($headerRow);
        if ($site) {
            $site = Craft::$app->getSites()->getSiteByHandle($site);
            $redirects = RedirectRecord::findAll(['siteId' => $site->id]);
        } else {
            $redirects = RedirectRecord::find()->all();
        }
        /** @var RedirectRecord[] $redirects */
        foreach ($redirects as $redirect) {
            $row = Row::fromValues([
                $redirect->pattern,
                $redirect->redirect,
                $redirect->method,
                $redirect->siteId ? Craft::$app->getSites()->getSiteById($redirect->siteId)->name : 'All Sites',
                $redirect->dateLastHit ? DateTimeHelper::toDateTime($redirect->dateLastHit)->format('Y-m-d h:i:s') : '',
                $redirect->counter,
            ]);
            $writer->addRow($row);
        }
        $writer->close();
        return Craft::$app->getResponse()->sendFile($path);
    }

    public function actionImport()
    {
        $filename = self::IMPORT_FILE;
        $filePath = Craft::$app->getPath()->getTempPath() . DIRECTORY_SEPARATOR . $filename;
        if (!file_exists($filePath)) {
            return $this->redirect(UrlHelper::cpUrl('seo-fields/redirects'));
        }
        $headers = $this->getHeaders($filePath);
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

        $rows = $this->getRows($filePath);
        $headers = $this->getHeaders($filePath);

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

    public function actionClearAll()
    {
        SeoFields::getInstance()->redirectService->deleteAll();
        return $this->redirect(UrlHelper::cpUrl('seo-fields/redirects'));
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

    private function getHeaders(string $filePath): array
    {
        $reader = new CsvReader();
        $reader->open($filePath);

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $reader->close();
                return $row->toArray();
            }
        }

        $reader->close();
        return [];
    }

    private function getRows(string $filePath): array
    {
        $reader = new CsvReader();
        $reader->open($filePath);

        $rows = [];
        $isFirstRow = true;
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                if ($isFirstRow) {
                    $isFirstRow = false;
                    continue;
                }
                $rows[] = $row->toArray();
            }
        }

        $reader->close();
        return $rows;
    }
}
