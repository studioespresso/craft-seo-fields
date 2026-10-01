<?php

namespace studioespresso\seofields\services;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Url;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use studioespresso\seofields\records\RedirectRecord;

/**
 * @author    Studio Espresso
 *
 * @since     1.0.0
 */
class RedirectService
{
    /** @var array<int, array<int, string>> Element URIs per site, captured before a save */
    private array $oldUris = [];

    public function trackElementUris(ElementInterface $element): void
    {
        if (empty($this->oldUris[$element->id])) {
            $this->oldUris[$element->id] = $this->getElementUris($element);
        }
    }

    public function handleUriChange(ElementInterface $element): void
    {
        if (empty($this->oldUris[$element->id])) {
            return;
        }

        foreach ($this->oldUris[$element->id] as $siteId => $oldUri) {
            $newUri = Elements::getElementUriForSite($element->id, $siteId);
            // It's possible that the element has no URI in this site (https://github.com/studioespresso/craft-seo-fields/issues/116)
            if (! $newUri) {
                continue;
            }

            if (Cms::config()->addTrailingSlashesToUrls) {
                $oldUri = rtrim($oldUri, '/').'/';
                $newUri = rtrim($newUri, '/').'/';
            }

            if ($newUri !== $oldUri) {
                $this->saveRedirect(new RedirectRecord([
                    'pattern' => parse_url(Url::siteUrl($oldUri, siteId: $siteId), PHP_URL_PATH),
                    'sourceMatch' => 'path',
                    'redirect' => Url::siteUrl($newUri, siteId: $siteId),
                    'matchType' => 'exact',
                    'siteId' => $siteId,
                    'method' => 301,
                ]));
            }
        }

        unset($this->oldUris[$element->id]);
    }

    /**
     * Builds the response for a matched redirect and records the hit.
     *
     * @param  string|null  $url  The target, already resolved for regex redirects
     */
    public function redirectResponse(RedirectRecord $redirect, Request $request, ?string $url = null): RedirectResponse
    {
        $redirect->counter = ($redirect->counter ?? 0) + 1;
        $redirect->dateLastHit = now();
        $redirect->save();

        $url ??= $redirect->siteId
            ? Url::siteUrl($redirect->redirect, siteId: $redirect->siteId)
            : $redirect->redirect;

        if ($query = $request->getQueryString()) {
            $url .= (str_contains($url, '?') ? '&' : '?').$query;
        }

        return new RedirectResponse($url, $redirect->method);
    }

    public function saveRedirect(RedirectRecord $redirect): bool
    {
        // "0" is the "All sites" option
        $redirect->siteId = $redirect->siteId ?: null;

        if ($redirect->sourceMatch !== 'url' && ! str_starts_with($redirect->pattern, '/')) {
            $redirect->pattern = '/'.$redirect->pattern;
        }

        return $redirect->save();
    }

    /**
     * @param  array<int, array<int, string>>  $rows  CSV rows without the header
     * @param  array{patternCol: int, redirectCol: int, siteId: int|string|null, method: int|string}  $settings
     * @return array{imported: array, invalid: array}
     */
    public function import(array $rows, array $settings): array
    {
        $imported = [];
        $invalid = [];

        foreach ($rows as $row) {
            $row = array_values($row);
            $pattern = (string) ($row[$settings['patternCol']] ?? '');
            $redirect = (string) ($row[$settings['redirectCol']] ?? '');

            if ($pattern === $redirect) {
                continue;
            }
            if ($pattern === '' || ! str_starts_with($redirect, '/')) {
                $invalid[] = $row;

                continue;
            }

            $this->saveRedirect(new RedirectRecord([
                'pattern' => $pattern,
                'redirect' => $redirect,
                'matchType' => 'exact',
                'sourceMatch' => 'path',
                'siteId' => $settings['siteId'],
                'method' => (int) $settings['method'],
            ]));
            $imported[] = $row;
        }

        return ['imported' => $imported, 'invalid' => $invalid];
    }

    /** @return array<int, string> */
    private function getElementUris(ElementInterface $element): array
    {
        $uris = [];
        if (! $element->id || ElementHelper::isDraftOrRevision($element)) {
            return $uris;
        }

        foreach (Sites::getAllSites(true) as $site) {
            if ($uri = Elements::getElementUriForSite($element->id, $site->id)) {
                $uris[$site->id] = $uri;
            }
        }

        return $uris;
    }
}
