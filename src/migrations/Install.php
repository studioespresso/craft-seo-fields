<?php

namespace studioespresso\seofields\migrations;

use Craft;
use craft\db\Migration;
use studioespresso\seofields\models\SeoDefaultsModel;
use studioespresso\seofields\records\DefaultsRecord;

/***
 * @author    Studio Espresso
 * @package   SeoFields
 * @since     1.0.0
 */
class Install extends Migration
{
    // Public Properties
    // =========================================================================
    public $driver;

    // Public Methods
    // =========================================================================
    public function safeUp()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        if ($this->createTables()) {
            $this->addForeignKeys();
            // Refresh the db schema caches
            Craft::$app->db->schema->refresh();
            $this->createSiteDefaults();
        }

        return true;
    }

    public function safeDown()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        $this->removeTables();
        return true;
    }

    // Protected Methods
    // =========================================================================
    protected function createSiteDefaults()
    {
        $sites = Craft::$app->getSites()->getAllSiteIds();
        foreach($sites as $siteId) {
            $default = new DefaultsRecord();
            $default->setAttribute('siteId', $siteId);
            $default->setAttribute('enableRobots', true);
            $default->setAttribute('robots', file_get_contents(CRAFT_VENDOR_PATH . '/studioespresso/craft-seo-fields/src/templates/_placeholder/_robots.twig'));
            if($default->validate()) {
                $default->save();
            }
        }
    }

    protected function createTables()
    {
        $tablesCreated = false;
        $tableSchema = Craft::$app->db->schema->getTableSchema(DefaultsRecord::tableName());
        if ($tableSchema === null) {
            $tablesCreated = true;
            $this->createTable(
                DefaultsRecord::tableName(),
                [
                    'id' => $this->primaryKey(),
                    'siteId' => $this->integer(11)->notNull(),
                    'defaultMeta' => $this->text(),
                    'enableRobots' => $this->boolean()->defaultValue(1),
                    'robots' => $this->text(),
                    'sitemap' => $this->text(),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'uid' => $this->uid(),
                ]
            );
        }
        return $tablesCreated;
    }

    protected function addForeignKeys()
    {
        // $name, $table, $columns, $refTable, $refColumns, $delete = null, $update = null)
        $this->addForeignKey(
            $this->db->getForeignKeyName(DefaultsRecord::tableName(), 'siteId'),
            DefaultsRecord::tableName(),
            'siteId',
            '{{%sites}}',
            'id',
            'CASCADE'
        );

    }

    protected function removeTables()
    {
        $this->dropTableIfExists(DefaultsRecord::tableName());
    }
}