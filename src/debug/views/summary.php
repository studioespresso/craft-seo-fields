<?php
/** @var studioespresso\seofields\debug\SchemaPanel $panel */
$nodes = $panel->data['nodes'] ?? 0;
$warnings = $panel->data['warnings'] ?? [];
$warningCount = count($warnings);
?>
<div class="yii-debug-toolbar__block">
    <a href="<?= $panel->getUrl() ?>">
        Schema <span class="yii-debug-toolbar__label <?= $warningCount > 0 ? 'yii-debug-toolbar__label_warning' : 'yii-debug-toolbar__label_success' ?>"><?= $nodes ?><?= $warningCount > 0 ? " ⚠ {$warningCount}" : '' ?></span>
    </a>
</div>
