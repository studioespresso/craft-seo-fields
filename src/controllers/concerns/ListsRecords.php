<?php

namespace studioespresso\seofields\controllers\concerns;

use CraftCms\Cms\Support\Facades\Sites;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Search, sorting and pagination for the server-rendered 404 and redirect lists.
 */
trait ListsRecords
{
    /**
     * @param  string[]  $searchable  Columns `?search=` looks in
     * @param  string[]  $sortable  Columns `?sort=` accepts; the first is the default
     */
    protected function paginate(Builder $query, Request $request, array $searchable, array $sortable): LengthAwarePaginator
    {
        $search = trim((string) $request->query('search'));
        if ($search !== '') {
            $like = '%'.addcslashes($search, '%_\\').'%';
            $query->where(function (Builder $query) use ($searchable, $like) {
                foreach ($searchable as $column) {
                    $query->orWhere($column, 'like', $like);
                }
            });
        }

        $sort = in_array($request->query('sort'), $sortable, true) ? $request->query('sort') : $sortable[0];
        $direction = $request->query('dir') === 'asc' ? 'asc' : 'desc';

        /** @var LengthAwarePaginator $rows */
        $rows = $query->orderBy($sort, $direction)->paginate(50);

        return $rows->withQueryString();
    }

    /** The variables the `_includes/_list` macros expect */
    protected function listVariables(LengthAwarePaginator $rows, Request $request): array
    {
        return ['list' => [
            'rows' => $rows,
            'query' => array_filter($request->query(), 'is_scalar'),
            'baseUrl' => $request->url(),
            // Site names by ID, only when there's more than one site to tell apart
            'sites' => Sites::isMultiSite() ? Sites::getAllSites()->mapWithKeys(fn ($site) => [$site->id => $site->getName()])->all() : null,
        ]];
    }
}
