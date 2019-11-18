<?php

namespace studioespresso\seofields\migrations;

use Craft;
use craft\db\Migration;
use studioespresso\seofields\records\NotFoundRecord;

/**
 * m191114_182559_addNotFoundTable migration.
 */
class m191114_182559_addNotFoundTable extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $tablesCreated = false;
        $tableSchema = Craft::$app->db->schema->getTableSchema(NotFoundRecord::tableName());
        if ($tableSchema === null) {
            $tablesCreated = true;
            $this->createTable(
                NotFoundRecord::tableName(),
                [
                    'id' => $this->primaryKey(),
                    'siteId' => $this->integer(11)->notNull(),
                    'url' => $this->text(),
                    'handled' => $this->boolean()->defaultValue(0),
                    'counter' => $this->bigInteger(),
                    'redirect' => $this->integer(11),
                    'dateLastHit' => $this->dateTime()->notNull(),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'uid' => $this->uid(),
                ]
            );
        }

        if($tablesCreated) {
            $this->addForeignKey(
                $this->db->getForeignKeyName(NotFoundRecord::tableName(), 'siteId'),
                NotFoundRecord::tableName(),
                'siteId',
                '{{%sites}}',
                'id',
                'CASCADE'
            );
            $this->addForeignKey(
                $this->db->getForeignKeyName(NotFoundRecord::tableName(), 'redirect'),
                NotFoundRecord::tableName(),
                'redirect',
                '{{%seofields_redirect}}',
                'id',
                'SET NULL'
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m191114_182559_addNotFoundTable cannot be reverted.\n";
        return false;
    }
}
