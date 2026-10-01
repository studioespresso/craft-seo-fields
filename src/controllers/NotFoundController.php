<?php

namespace studioespresso\seofields\controllers;

use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Support\Facades\Sites;
use Illuminate\Http\Request;
use studioespresso\seofields\controllers\concerns\ListsRecords;
use studioespresso\seofields\controllers\concerns\SeoCpScreen;
use studioespresso\seofields\records\NotFoundRecord;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

class NotFoundController
{
    use ListsRecords;
    use RespondsWithFlash;
    use SeoCpScreen;

    public function index(Request $request): CpScreenResponse
    {
        $site = Sites::isMultiSite() && $request->query('site') ? $this->site($request) : null;
        $this->registerListStyles();
        $display = $request->query('display', 'all');

        $query = NotFoundRecord::query()->with('redirectRecord');
        if ($site) {
            $query->where('siteId', $site->id);
        }
        match ($display) {
            'handled' => $query->where('handled', true),
            'unhandled' => $query->where('handled', false),
            default => null,
        };

        return $this->screen(t('404 Overview', category: 'seo-fields'), $site)
            ->toolbarTemplate('seo-fields/_notfound/_buttons', [
                'display' => $display,
                'viewOptions' => [
                    ['value' => 'all', 'label' => t("Show all 404's", category: 'seo-fields')],
                    ['value' => 'unhandled', 'label' => t('Items without a redirect', category: 'seo-fields')],
                    ['value' => 'handled', 'label' => t('Items with a redirect', category: 'seo-fields')],
                ],
            ])
            ->contentTemplate('seo-fields/_notfound/_content', [
                ...$this->listVariables($this->paginate($query, $request, ['urlPath', 'fullUrl'], ['counter', 'dateLastHit', 'handled'])
                    ->through(fn (NotFoundRecord $row) => [
                        'id' => $row->id,
                        'urlPath' => $row->urlPath,
                        'counter' => $row->counter,
                        'siteId' => $row->siteId,
                        'dateLastHit' => $row->dateLastHit,
                        'redirectId' => $row->redirectRecord?->id,
                        'redirectUrl' => $row->redirectRecord?->redirect,
                    ]), $request),
            ])
            ->inertiaPage('cp/Screen');
    }

    public function delete(int $id): Response
    {
        NotFoundRecord::query()->whereKey($id)->delete();

        return $this->asSuccess(t('404 removed', category: 'seo-fields'), redirect: url()->previous());
    }

    public function clearAll(): Response
    {
        NotFoundRecord::query()->delete();

        return $this->asSuccess(t("All 404's removed", category: 'seo-fields'));
    }
}
