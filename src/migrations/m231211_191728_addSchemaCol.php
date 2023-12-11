<?php

namespace studioespresso\seofields\migrations;

use Craft;
use craft\db\Migration;
use studioespresso\seofields\records\DefaultsRecord;

/**
 * m231211_191728_addSchemaCol migration.
 */
class m231211_191728_addSchemaCol extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->addColumn(
            DefaultsRecord::tableName(),
            'schema',
            $this->text()->after('robots')
        );


        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m231211_191728_addSchemaCol cannot be reverted.\n";
        return false;
    }
}
