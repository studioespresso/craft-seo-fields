<?php

namespace studioespresso\seofields\http;

use Closure;
use CraftCms\Cms\Support\Facades\Sites;
use Illuminate\Http\Request;
use studioespresso\seofields\SeoFields;
use Symfony\Component\HttpFoundation\Response;

/**
 * Logs site 404s and swaps them for a redirect when one matches.
 *
 * Craft 6 returns unmatched site URLs as plain 404 responses rather than exceptions, so this wraps the site routes.
 */
class HandleNotFound
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (
            $response->getStatusCode() !== 404
            || ! $request->isSiteRequest()
            || $request->isPreview()
            || str_starts_with($request->path(), 'cpresources')
        ) {
            return $response;
        }

        return SeoFields::getInstance()->notFoundService->handleNotFound($request, Sites::getCurrentSite()) ?? $response;
    }
}
