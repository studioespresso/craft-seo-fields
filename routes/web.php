<?php

use Illuminate\Support\Facades\Route;
use studioespresso\seofields\controllers\RobotsController;
use studioespresso\seofields\controllers\SitemapController;

Route::get('robots.txt', [RobotsController::class, 'render']);
Route::get('sitemap.xml', [SitemapController::class, 'index']);
Route::get('sitemap_{siteId}_entry_{sectionId}_{handle}.xml', [SitemapController::class, 'detail'])
    ->whereNumber(['siteId', 'sectionId']);
