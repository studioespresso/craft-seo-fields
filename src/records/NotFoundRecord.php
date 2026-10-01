<?php

namespace studioespresso\seofields\records;

use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Shared\Concerns\HasUid;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $siteId
 * @property string|null $fullUrl
 * @property string|null $urlPath
 * @property string|null $urlParams
 * @property string|null $referrer
 * @property bool $handled
 * @property int|null $counter
 * @property int|null $redirect id of the matching redirect
 * @property \DateTimeInterface $dateLastHit
 */
class NotFoundRecord extends BaseModel
{
    use HasUid;

    protected $table = 'seofields_404';

    protected $casts = [
        'siteId' => 'int',
        'handled' => 'bool',
        'counter' => 'int',
        'redirect' => 'int',
        'dateLastHit' => 'datetime',
    ];

    /** @return BelongsTo<RedirectRecord, $this> */
    public function redirectRecord(): BelongsTo
    {
        return $this->belongsTo(RedirectRecord::class, 'redirect');
    }
}
