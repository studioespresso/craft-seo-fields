<?php

namespace studioespresso\seofields\records;

use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Shared\Concerns\HasUid;

/**
 * @property int $id
 * @property int $siteId
 * @property array|null $defaultMeta
 * @property bool $enableRobots
 * @property string|null $robots
 * @property string|null $schema
 * @property string|null $sitemap
 */
class DefaultsRecord extends BaseModel
{
    use HasUid;

    protected $table = 'seofields_data';

    protected $casts = [
        'siteId' => 'int',
        'defaultMeta' => 'json',
        'enableRobots' => 'bool',
    ];
}
