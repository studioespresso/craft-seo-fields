<?php

namespace studioespresso\seofields\console;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Entry\Elements\Entry;
use Illuminate\Console\Command;
use studioespresso\seofields\jobs\MigrateFieldDataJob;

/**
 * Copies plain title/description/image fields into the SEO field, one queue job per entry.
 */
class MigrateFieldsCommand extends Command
{
    use CraftCommand;

    protected $signature = 'seo-fields:migrate-fields
        {--fieldHandle=seo : The SEO field to fill}
        {--metaTitle=metaTitle : The field to copy the meta title from}
        {--metaDescription=metaDescription : The field to copy the meta description from}
        {--metaImage=metaImage : The assets field to copy the social image from}';

    protected $description = 'Copies existing meta title, description and image fields into an SEO field.';

    protected $aliases = ['seo-fields/migrate/fields'];

    public function handle(): void
    {
        $handle = $this->option('fieldHandle');
        $queued = 0;

        foreach (Entry::find()->status(null)->get() as $entry) {
            if (! $entry->getFieldLayout()?->getFieldByHandle($handle)) {
                continue;
            }

            MigrateFieldDataJob::dispatch($entry->id, $entry->siteId, $handle, [
                'metaTitle' => $this->option('metaTitle'),
                'metaDescription' => $this->option('metaDescription'),
                'metaImage' => $this->option('metaImage'),
            ]);
            $queued++;
        }

        $this->components->info("Queued $queued entries.");
    }
}
