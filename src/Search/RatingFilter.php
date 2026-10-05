<?php

namespace Prm\TradeFeedback\Search;

use Flarum\Foundation\ValidationException;
use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\Filter\FilterInterface;
use Flarum\Search\SearchState;
use Flarum\Search\ValidateFilterTrait;

/**
 * @implements FilterInterface<DatabaseSearchState>
 */
class RatingFilter implements FilterInterface
{
    use ValidateFilterTrait;

    public function getFilterKey(): string
    {
        return 'rating';
    }

    public function filter(SearchState $state, string|array $value, bool $negate): void
    {
        $rating = $this->asString($value);

        if ($rating === 'all') {
            return;
        }

        $map = [
            'positive' => 1,
            'neutral' => 0,
            'negative' => -1,
        ];

        if (! array_key_exists($rating, $map)) {
            throw new ValidationException([
                'rating' => 'Rating must be all, positive, neutral, or negative.',
            ]);
        }

        $state->getQuery()->where('rating', $negate ? '!=' : '=', $map[$rating]);
    }
}
