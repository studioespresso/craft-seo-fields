<?php

use Illuminate\Support\Facades\Route;
use studioespresso\seofields\controllers\DefaultsController;
use studioespresso\seofields\controllers\NotFoundController;
use studioespresso\seofields\controllers\RedirectsController;
use studioespresso\seofields\controllers\RobotsController;
use studioespresso\seofields\controllers\SchemaController;
use studioespresso\seofields\controllers\SitemapController;

Route::middleware(['auth', 'can:accessCp', 'can:accessPlugin-seo-fields'])->prefix('seo-fields')->group(function () {
    Route::get('', [DefaultsController::class, 'index']);

    Route::middleware('can:seo-fields:default')->group(function () {
        Route::get('defaults', [DefaultsController::class, 'edit']);
        Route::post('defaults', [DefaultsController::class, 'store']);
    });

    Route::middleware('can:seo-fields:robots')->group(function () {
        Route::get('robots', [RobotsController::class, 'edit']);
        Route::post('robots', [RobotsController::class, 'store']);
    });

    Route::middleware('can:seo-fields:sitemap')->group(function () {
        Route::get('sitemap', [SitemapController::class, 'edit']);
        Route::post('sitemap', [SitemapController::class, 'store']);
    });

    Route::middleware('can:seo-fields:schema')->group(function () {
        Route::get('schema', [SchemaController::class, 'edit']);
        Route::post('schema', [SchemaController::class, 'store']);
    });

    Route::middleware('can:seo-fields:notfound')->prefix('not-found')->group(function () {
        Route::get('', [NotFoundController::class, 'index']);
        Route::post('{id}/delete', [NotFoundController::class, 'delete'])->whereNumber('id');
        Route::post('clear-all', [NotFoundController::class, 'clearAll']);
    });

    Route::middleware('can:seo-fields:redirects')->prefix('redirects')->group(function () {
        Route::get('', [RedirectsController::class, 'index']);
        Route::post('{id}/delete', [RedirectsController::class, 'delete'])->whereNumber('id');
        Route::get('export', [RedirectsController::class, 'export']);
        Route::post('upload', [RedirectsController::class, 'upload']);
        Route::get('import', [RedirectsController::class, 'import']);
        Route::post('import', [RedirectsController::class, 'runImport']);
        Route::get('import/results', [RedirectsController::class, 'importResults']);
        Route::post('clear-all', [RedirectsController::class, 'clearAll'])->middleware('can:admin');
        Route::get('new', [RedirectsController::class, 'edit']);
        Route::post('new', [RedirectsController::class, 'store']);
        Route::get('{id}', [RedirectsController::class, 'edit'])->whereNumber('id');
        Route::post('{id}', [RedirectsController::class, 'store'])->whereNumber('id');
    });
});
