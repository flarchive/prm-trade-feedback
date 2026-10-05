<?php

namespace Prm\TradeFeedback\Search;

use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\SearchCriteria;
use Flarum\Search\SearchState;
use Illuminate\Support\Arr;

class ApplyUserDirection
{
    public function __invoke(SearchState $state, SearchCriteria $criteria): void
    {
        $userId = Arr::get($criteria->filters, 'user');

        if ($userId === null || $userId === '') {
            return;
        }

        $direction = Arr::get($criteria->filters, 'direction', 'received');
        $column = $direction === 'given' ? 'from_user_id' : 'to_user_id';

        /** @var DatabaseSearchState $state */
        $state->getQuery()->where($column, (int) $userId);
    }
}
