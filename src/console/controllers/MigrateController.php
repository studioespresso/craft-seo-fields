<?php

namespace studioespresso\seofields\console\controllers;

use Craft;
use craft\db\Query;
use craft\elements\Entry;
use craft\errors\SiteNotFoundException;
use craft\helpers\App;
use craft\helpers\Console;
use craft\helpers\Db;
use craft\helpers\UrlHelper;
use craft\services\Sections;
use ether\seo\models\data\SeoData;
use studioespresso\seofields\fields\SeoField;
use studioespresso\seofields\models\SeoDefaultsModel;
use studioespresso\seofields\records\DefaultsRecord;
use studioespresso\seofields\SeoFields;
use studioespresso\seofields\services\migrate\Ether;
use Twig\Markup;
use yii\console\Controller;

class MigrateController extends Controller
{

    public $newHandle = 'newSeo';
    public $oldHandle = 'seo';
    public $siteId;
    public $titleSeperator;
    public $fieldHandle = 'seo';
    public $metaTitle;
    public $metaDescription;

    public function options($actionId)
    {
        switch ($actionId) {
            case 'ether':
                return ['oldHandle', 'newHandle', 'siteId', 'titleSeperator'];
            case 'fields':
                return ['fieldHandle', 'metaTitle', 'metaDescription'];
        }
    }

    public function actionEther()
    {
        if (!Craft::$app->getPlugins()->isPluginInstalled('seo')) {
            $this->stdout("ether/seo not installed." . PHP_EOL, Console::FG_YELLOW);
            $this->stdout(PHP_EOL);
        }

        if (!version_compare(Craft::$app->getPlugins()->getPlugin('seo')->getVersion(), '3.6.0', '>=')) {
            $this->stdout("Migrating content from ether/seo to SEO Fields requires version 3.6.0 or higher for ether/seo" . PHP_EOL, Console::FG_YELLOW);
        }

        $etherMigration = new Ether();
        $etherMigration->migrate($this->oldHandle = 'seo', $this->newHandle = 'newSeo', $this->siteId, $this->titleSeperator);

    }

    public function actionFields()
    {
        $field = Craft::$app->getFields()->getFieldByHandle($this->fieldHandle);

        $query = new Query();
        $query->select('entrytypes.sectionId as sectionId');
        $query->addSelect('entrytypes.id as typeId');
        $query->from('{{%fieldlayoutfields}}');
        $query->where(Db::parseParam('fieldId', $field->id));
        $query->leftJoin('{{%entrytypes}}', 'fieldlayoutfields.layoutId = entrytypes.fieldLayoutId');

        App::maxPowerCaptain();
        foreach ($query->all() as $data) {
            $section = Craft::$app->getSections()->getSectionById($data['sectionId']);
            $type = Craft::$app->getSections()->getEntryTypeById($data['typeId']);
            $entries = Entry::findAll(['sectionId' => $data['sectionId'], 'typeId' => $data['typeId']]);
            $this->stdout("Processing entries in {$section->name} ($type->name)" . PHP_EOL, Console::FG_GREEN);
            foreach($entries as $entry) {
                Craft::$app->getQueue()->push(new SeoUpdateJob([
                    'entryId' => $entry->id,
                    'fieldHandle' => $this->fieldHandle,
                    'metaTitle' => $this->metaTitle,
                    'metaDescription' => $this->metaDescription,
                ]));
        };
    }


}
