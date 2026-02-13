<?php

namespace studioespresso\seofields\migrations;

use Craft;
use craft\db\Migration;
use studioespresso\seofields\records\DefaultsRecord;

/**
 * m260213_181738_addLlmSettings migration.
 */
class m260213_181738_addLlmSettings extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->addColumn(
            DefaultsRecord::tableName(),
            'enableLlm',
             $this->boolean()->after('robots')
        );
        $this->addColumn(
            DefaultsRecord::tableName(),
            'llm',
            $this->json()->after('enableLlm')
        );

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m260213_181738_addLlmSettings cannot be reverted.\n";
        return false;
    }
}
