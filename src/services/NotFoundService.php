<?php

namespace studioespresso\seofields\services;

use CraftCms\Cms\Site\Data\Site;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use studioespresso\seofields\records\NotFoundRecord;
use studioespresso\seofields\records\RedirectRecord;
use studioespresso\seofields\SeoFields;
use Throwable;

/**
 * @author    Studio Espresso
 *
 * @since     1.0.0
 */
class NotFoundService
{
    /**
     * Logs a site 404 and returns a redirect response when a redirect matches it.
     */
    public function handleNotFound(Request $request, Site $site): ?RedirectResponse
    {
        try {
            $fullUrl = urldecode($request->fullUrl());
            $urlPath = urldecode($request->getRequestUri());

            $notFound = NotFoundRecord::query()
                ->where('siteId', $site->id)
                ->where('fullUrl', $fullUrl)
                ->where('urlPath', $urlPath)
                ->first();

            if ($notFound) {
                $notFound->counter = ($notFound->counter ?? 0) + 1;
            } else {
                $notFound = new NotFoundRecord([
                    'siteId' => $site->id,
                    'fullUrl' => $fullUrl,
                    'urlPath' => $urlPath,
                    'urlParams' => $request->getQueryString(),
                    'referrer' => $request->headers->get('referer'),
                    'handled' => false,
                    'counter' => 1,
                ]);
            }
            $notFound->dateLastHit = now();

            [$redirect, $url] = $this->getMatchingRedirect($notFound) ?? [null, null];
            if ($redirect) {
                $notFound->redirect = $redirect->id;
                $notFound->handled = true;
            }
            $notFound->save();

            $this->cleanup();

            return $redirect
                ? SeoFields::getInstance()->redirectService->redirectResponse($redirect, $request, $url)
                : null;
        } catch (Throwable $e) {
            Log::error($e->getMessage(), ['exception' => $e]);

            return null;
        }
    }

    public function markAsHandled(int $id): void
    {
        NotFoundRecord::query()->whereKey($id)->update(['handled' => true]);
    }

    /**
     * @return array{0: RedirectRecord, 1: string|null}|null The redirect and, for regex matches, the resolved target URL
     */
    private function getMatchingRedirect(NotFoundRecord $notFound): ?array
    {
        $candidates = [
            'path' => $notFound->urlPath,
            'pathWithoutParams' => parse_url($notFound->urlPath, PHP_URL_PATH),
            'url' => $notFound->fullUrl,
        ];

        foreach ($candidates as $sourceMatch => $pattern) {
            $redirect = $this->redirectsForSite($notFound->siteId)
                ->where('sourceMatch', $sourceMatch)
                ->where('pattern', $pattern)
                ->first();

            if ($redirect) {
                return [$redirect, null];
            }
        }

        // Regex redirects are matched in PHP
        $regexRedirects = $this->redirectsForSite($notFound->siteId)->where('matchType', 'regexMatch')->get();
        foreach ($regexRedirects as $redirect) {
            $pattern = '`'.$redirect->pattern.'`i';
            if (@preg_match($pattern, $notFound->urlPath)) {
                $subject = str_contains($redirect->redirect, 'http') ? $notFound->urlPath : $notFound->fullUrl;

                // Replace placeholders ($1, $2, etc.) with the captured groups
                return [$redirect, preg_replace($pattern, $redirect->redirect, $subject)];
            }
        }

        return null;
    }

    /** Redirects that apply to the site, including the ones for all sites */
    private function redirectsForSite(int $siteId)
    {
        return RedirectRecord::query()->where(fn ($query) => $query->where('siteId', $siteId)->orWhereNull('siteId'));
    }

    /** Keeps the 404 log under the `notFoundLimit` setting, dropping the oldest rows */
    public function cleanup(): void
    {
        $max = SeoFields::getInstance()->getSettings()->notFoundLimit;
        if ($max === null) {
            return;
        }

        $excess = NotFoundRecord::query()->count() - $max;
        if ($excess > 0) {
            NotFoundRecord::query()->orderBy('dateLastHit')->limit($excess)->delete();
        }
    }
}
