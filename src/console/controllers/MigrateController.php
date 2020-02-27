<?php

namespace studioespresso\seofields\console\controllers;

use Craft;
use craft\elements\Entry;
use craft\errors\SiteNotFoundException;
use craft\helpers\App;
use craft\helpers\Console;
use craft\helpers\UrlHelper;
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

    public $titleSeperator = '|';

    public function options($actionId)
    {
        switch ($actionId) {
            case 'ether':
                return ['oldHandle', 'newHandle', 'siteId', 'titleSeperator'];
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
}
