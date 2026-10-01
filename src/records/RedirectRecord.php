<?php

namespace studioespresso\seofields\records;

use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Shared\Concerns\HasUid;

/**
 * @property int $id
 * @property int|null $siteId null applies to all sites
 * @property string $pattern
 * @property string|null $sourceMatch path|pathWithoutParams|url
 * @property string $redirect
 * @property string|null $matchType exact|regexMatch
 * @property int|null $counter
 * @property int $method
 * @property \DateTimeInterface|null $dateLastHit
 */
class RedirectRecord extends BaseModel
{
    use HasUid;

    protected $table = 'seofields_redirects';

    protected $casts = [
        'siteId' => 'int',
        'counter' => 'int',
        'method' => 'int',
        'dateLastHit' => 'datetime',
    ];
}
