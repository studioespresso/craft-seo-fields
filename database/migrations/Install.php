<?php

use CraftCms\Cms\Database\Migration;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Support\Facades\Sites;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use studioespresso\seofields\records\DefaultsRecord;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('seofields_data')) {
            Schema::create('seofields_data', function (Blueprint $table) {
                $table->integer('id', true);
                $table->integer('siteId');
                $table->text('defaultMeta')->nullable();
                $table->boolean('enableRobots')->default(true)->nullable();
                $table->text('robots')->nullable();
                $table->text('schema')->nullable();
                $table->text('sitemap')->nullable();
                $table->dateTime('dateCreated');
                $table->dateTime('dateUpdated');
                $table->char('uid', 36)->default('0');
                $table->foreign('siteId')->references('id')->on(Table::SITES)->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('seofields_redirects')) {
            Schema::create('seofields_redirects', function (Blueprint $table) {
                $table->integer('id', true);
                $table->integer('siteId')->nullable();
                $table->string('pattern');
                $table->string('sourceMatch')->nullable();
                $table->string('redirect');
                $table->string('matchType')->nullable();
                $table->bigInteger('counter')->nullable();
                $table->integer('method');
                $table->dateTime('dateLastHit')->nullable();
                $table->dateTime('dateCreated');
                $table->dateTime('dateUpdated');
                $table->char('uid', 36)->default('0');
                $table->foreign('siteId')->references('id')->on(Table::SITES)->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('seofields_404')) {
            Schema::create('seofields_404', function (Blueprint $table) {
                $table->integer('id', true);
                $table->integer('siteId');
                $table->text('fullUrl')->nullable();
                $table->text('urlPath')->nullable();
                $table->text('urlParams')->nullable();
                $table->text('referrer')->nullable();
                $table->boolean('handled')->default(false)->nullable();
                $table->bigInteger('counter')->nullable();
                $table->integer('redirect')->nullable();
                $table->dateTime('dateLastHit');
                $table->dateTime('dateCreated');
                $table->dateTime('dateUpdated');
                $table->char('uid', 36)->default('0');
                $table->foreign('siteId')->references('id')->on(Table::SITES)->cascadeOnDelete();
                $table->foreign('redirect')->references('id')->on('seofields_redirects')->nullOnDelete();
            });
        }

        $robots = file_get_contents(dirname(__DIR__, 2).'/src/templates/_placeholder/_robots.twig');
        foreach (Sites::getAllSiteIds() as $siteId) {
            DefaultsRecord::query()->firstOrCreate(
                ['siteId' => $siteId],
                ['enableRobots' => true, 'robots' => $robots],
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('seofields_data');
        Schema::dropIfExists('seofields_404');
        Schema::dropIfExists('seofields_redirects');
    }
};
