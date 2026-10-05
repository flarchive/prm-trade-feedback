<?php

namespace Prm\TradeFeedback\Search;

use Flarum\Search\Database\AbstractSearcher;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;
use Prm\TradeFeedback\TradeFeedback;

class TradeFeedbackSearcher extends AbstractSearcher
{
    public function getQuery(User $actor): Builder
    {
        return TradeFeedback::query();
    }
}
