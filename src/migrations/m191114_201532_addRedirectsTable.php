<?php

namespace studioespresso\seofields\migrations;

use Craft;
use craft\db\Migration;
use studioespresso\seofields\records\RedirectRecord;

/**
 * m191114_201532_addRedirectsTable migration.
 */
class m191114_201532_addRedirectsTable extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $tablesCreated = false;
        $tableSchema = Craft::$app->db->schema->getTableSchema(RedirectRecord::tableName());
        if ($tableSchema === null) {
            $tablesCreated = true;
            $this->createTable(
                RedirectRecord::tableName(),
                [
                    'id' => $this->primaryKey(),
                    'siteId' => $this->integer(11)->notNull(),
                    'pattern' => $this->text(255)->notNull(),
                    'redirect' => $this->text(255)->notNull(),
                    'counter' => $this->bigInteger(),
                    'method' => $this->string(3)->notNull(),
                    'dateLastHit' => $this->dateTime()->notNull(),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'uid' => $this->uid(),
                ]
            );
        }

        if($tablesCreated) {
            $this->addForeignKey(
                $this->db->getForeignKeyName(RedirectRecord::tableName(), 'siteId'),
                RedirectRecord::tableName(),
                'siteId',
                '{{%sites}}',
                'id',
                'CASCADE'
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m191114_201532_addRedirectsTable cannot be reverted.\n";
        return false;
    }
}
