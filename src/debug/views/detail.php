<?php
/** @var studioespresso\seofields\debug\SchemaPanel $panel */
$schema = $panel->data["schema"] ?? null;
$json = $panel->data["json"] ?? null;
$warnings = $panel->data["warnings"] ?? [];
?>
<h1>Schema.org Markup</h1>

<?php if (!empty($warnings)): ?>
    <div style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 4px; padding: 12px 16px; margin-bottom: 16px;">
        <strong>Validation warnings (<?= count($warnings) ?>)</strong>
        <ul style="margin: 8px 0 0; padding-left: 20px;">
            <?php foreach ($warnings as $warning): ?>
                <li><?= htmlspecialchars($warning) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if (empty($schema)): ?>
    <p>No schema markup was generated for this request.</p>
<?php else: ?>
    <h3>Graph nodes (<?= count($schema["@graph"] ?? []) ?>)</h3>
    <table class="table table-condensed table-bordered table-striped table-hover" style="table-layout: fixed;">
        <thead>
            <tr>
                <th style="width: 200px;">Type</th>
                <th style="width: 200px;">ID</th>
                <th>Properties</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($schema["@graph"] ?? [] as $node): ?>
                <tr>
                    <td><code><?= htmlspecialchars(
                        is_array($node["@type"] ?? "")
                            ? implode(", ", $node["@type"])
                            : $node["@type"] ?? "—",
                    ) ?></code></td>
                    <td><code><?= htmlspecialchars(
                        $node["@id"] ?? "—",
                    ) ?></code></td>
                    <td style="white-space: normal;">
                        <?php
                        $props = array_filter(
                            $node,
                            fn($k) => !str_starts_with($k, "@"),
                            ARRAY_FILTER_USE_KEY,
                        );
                        foreach ($props as $key => $value): ?>
                            <strong><?= htmlspecialchars($key) ?>:</strong>
                            <?= htmlspecialchars(
                                is_array($value)
                                    ? json_encode(
                                        $value,
                                        JSON_UNESCAPED_SLASHES |
                                            JSON_UNESCAPED_UNICODE,
                                    )
                                    : (string) $value,
                            ) ?><br>
                        <?php endforeach;
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h3>JSON-LD Output</h3>
    <pre style="background: #1e1e1e; color: #d4d4d4; padding: 16px; border-radius: 4px; overflow-x: auto; font-size: 13px; line-height: 1.5;"><?= htmlspecialchars(
        $json,
    ) ?></pre>
<?php endif; ?>
