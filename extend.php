<?php

namespace Prm\TradeFeedback;

use Flarum\Api\Serializer\BasicUserSerializer;
use Flarum\Api\Serializer\ForumSerializer;
use Flarum\Api\Serializer\UserSerializer;
use Flarum\Extend;
use Flarum\User\User;
use Prm\TradeFeedback\Access\FeedbackPolicy;
use Prm\TradeFeedback\Api\Controller\CreateTradeFeedbackController;
use Prm\TradeFeedback\Api\Controller\DeleteTradeFeedbackController;
use Prm\TradeFeedback\Api\Controller\ListTradeFeedbacksController;
use Prm\TradeFeedback\Api\Controller\UpdateTradeFeedbackController;
use Prm\TradeFeedback\Forum\UserTradeContent;
use Prm\TradeFeedback\Notification\FeedbackReceivedBlueprint;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less')
        ->route('/u/{username}/trade', 'user.trade', UserTradeContent::class),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js'),

    new Extend\Locales(__DIR__.'/locale'),

    (new Extend\Routes('api'))
        ->get('/trade-feedbacks', 'trade-feedbacks.index', ListTradeFeedbacksController::class)
        ->post('/trade-feedbacks', 'trade-feedbacks.create', CreateTradeFeedbackController::class)
        ->patch('/trade-feedbacks/{id}', 'trade-feedbacks.update', UpdateTradeFeedbackController::class)
        ->delete('/trade-feedbacks/{id}', 'trade-feedbacks.delete', DeleteTradeFeedbackController::class),

    (new Extend\Model(User::class))
        ->hasMany('receivedTradeFeedbacks', TradeFeedback::class, 'to_user_id')
        ->hasMany('givenTradeFeedbacks', TradeFeedback::class, 'from_user_id')
        ->cast('trade_received_count', 'int')
        ->cast('trade_positive_count', 'int')
        ->cast('trade_neutral_count', 'int')
        ->cast('trade_negative_count', 'int')
        ->cast('trade_given_positive_count', 'int')
        ->cast('trade_given_neutral_count', 'int')
        ->cast('trade_given_negative_count', 'int'),

    (new Extend\ApiSerializer(BasicUserSerializer::class))
        ->attribute('tradeReceivedCount', function (BasicUserSerializer $serializer, User $user) {
            return (int) $user->trade_received_count;
        }),

    (new Extend\ApiSerializer(UserSerializer::class))
        ->attributes(function (UserSerializer $serializer, User $user) {
            $actor = $serializer->getActor();

            return [
                'tradeFeedbackStats' => TradeStats::forUser($user),
                'canGiveTradeFeedback' => ! $actor->isGuest()
                    && (int) $actor->id !== (int) $user->id
                    && $actor->can('create', TradeFeedback::class),
            ];
        }),

    (new Extend\ApiSerializer(ForumSerializer::class))
        ->attribute('canGiveTradeFeedback', function (ForumSerializer $serializer) {
            return $serializer->getActor()->can('create', TradeFeedback::class);
        }),

    (new Extend\Policy())
        ->modelPolicy(TradeFeedback::class, FeedbackPolicy::class),

    (new Extend\Notification())
        ->type(FeedbackReceivedBlueprint::class, BasicUserSerializer::class, ['alert']),

    (new Extend\Settings())
        ->default('prm-trade-feedback.notice', 'Sadece Türk Lirası geçerlidir. 200 TL altı işlemlerde ticaret puanı gönderilemez.')
        ->serializeToForum('tradeFeedbackNotice', 'prm-trade-feedback.notice'),
];
