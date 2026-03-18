<?php

namespace studioespresso\seofields\debug;

use Craft;
use yii\debug\Panel;

class SchemaPanel extends Panel
{
    public function getName(): string
    {
        return 'Schema';
    }

    public function getSummary(): string
    {
        return Craft::$app->getView()->render('@studioespresso/seofields/debug/views/summary', [
            'panel' => $this,
        ]);
    }

    public function getDetail(): string
    {
        return Craft::$app->getView()->render('@studioespresso/seofields/debug/views/detail', [
            'panel' => $this,
        ]);
    }

    public function save(): array
    {
        return $this->data ?? [];
    }
}
