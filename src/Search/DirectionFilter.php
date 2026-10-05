<?php

namespace Prm\TradeFeedback\Search;

use Flarum\Foundation\ValidationException;
use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\Filter\FilterInterface;
use Flarum\Search\SearchState;
use Flarum\Search\ValidateFilterTrait;

/**
 * Validates the direction filter; the query constraint is applied by {@see ApplyUserDirection}.
 *
 * @implements FilterInterface<DatabaseSearchState>
 */
class DirectionFilter implements FilterInterface
{
    use ValidateFilterTrait;

    public function getFilterKey(): string
    {
        return 'direction';
    }

    public function filter(SearchState $state, string|array $value, bool $negate): void
    {
        $direction = $this->asString($value);

        if (! in_array($direction, ['received', 'given'], true)) {
            throw new ValidationException([
                'direction' => 'Direction must be received or given.',
            ]);
        }
    }
}
