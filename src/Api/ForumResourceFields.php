<?php

namespace Prm\TradeFeedback\Api;

use Flarum\Api\Context;
use Flarum\Api\Schema;
use Prm\TradeFeedback\TradeFeedback;

class ForumResourceFields
{
    public function __invoke(): array
    {
        return [
            Schema\Boolean::make('canGiveTradeFeedback')
                ->get(fn (object $model, Context $context) => $context->getActor()->can('create', TradeFeedback::class)),
        ];
    }
}
