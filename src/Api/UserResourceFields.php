<?php

namespace Prm\TradeFeedback\Api;

use Flarum\Api\Context;
use Flarum\Api\Schema;
use Flarum\User\User;
use Prm\TradeFeedback\TradeFeedback;
use Prm\TradeFeedback\TradeStats;

class UserResourceFields
{
    public function __invoke(): array
    {
        return [
            Schema\Integer::make('tradeReceivedCount')
                ->get(fn (User $user) => (int) $user->trade_received_count),
            Schema\Arr::make('tradeFeedbackStats')
                ->get(fn (User $user) => TradeStats::forUser($user)),
            Schema\Boolean::make('canGiveTradeFeedback')
                ->get(function (User $user, Context $context) {
                    $actor = $context->getActor();

                    return ! $actor->isGuest()
                        && (int) $actor->id !== (int) $user->id
                        && $actor->can('create', TradeFeedback::class);
                }),
        ];
    }
}
