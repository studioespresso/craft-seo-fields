<?php
/** @var studioespresso\seofields\debug\SchemaPanel $panel */
$nodes = $panel->data['nodes'] ?? 0;
?>
<div class="yii-debug-toolbar__block">
    <a href="<?= $panel->getUrl() ?>">
        Schema <span class="yii-debug-toolbar__label"><?= $nodes ?></span>
    </a>
</div>
