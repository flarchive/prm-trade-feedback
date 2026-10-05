<?php

namespace Prm\TradeFeedback;

use Flarum\Api\Resource as CoreResource;
use Flarum\Extend;
use Flarum\Search\Database\DatabaseSearchDriver;
use Flarum\User\User;
use Prm\TradeFeedback\Access\FeedbackPolicy;
use Prm\TradeFeedback\Api\ForumResourceFields;
use Prm\TradeFeedback\Api\Resource\TradeFeedbackResource;
use Prm\TradeFeedback\Api\UserResourceFields;
use Prm\TradeFeedback\Notification\FeedbackReceivedBlueprint;
use Prm\TradeFeedback\Search\ApplyUserDirection;
use Prm\TradeFeedback\Search\DirectionFilter;
use Prm\TradeFeedback\Search\RatingFilter;
use Prm\TradeFeedback\Search\TradeFeedbackSearcher;
use Prm\TradeFeedback\Search\UserFilter;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js'),

    new Extend\Locales(__DIR__.'/locale'),

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

    new Extend\ApiResource(TradeFeedbackResource::class),

    (new Extend\ApiResource(CoreResource\UserResource::class))
        ->fields(UserResourceFields::class),

    (new Extend\ApiResource(CoreResource\ForumResource::class))
        ->fields(ForumResourceFields::class),

    (new Extend\SearchDriver(DatabaseSearchDriver::class))
        ->addSearcher(TradeFeedback::class, TradeFeedbackSearcher::class)
        ->addFilter(TradeFeedbackSearcher::class, UserFilter::class)
        ->addFilter(TradeFeedbackSearcher::class, DirectionFilter::class)
        ->addFilter(TradeFeedbackSearcher::class, RatingFilter::class)
        ->addMutator(TradeFeedbackSearcher::class, ApplyUserDirection::class),

    (new Extend\Policy())
        ->modelPolicy(TradeFeedback::class, FeedbackPolicy::class),

    (new Extend\Notification())
        ->type(FeedbackReceivedBlueprint::class, ['alert']),

    (new Extend\Settings())
        ->default('prm-trade-feedback.notice', 'Sadece Türk Lirası geçerlidir. 200 TL altı işlemlerde ticaret puanı gönderilemez.')
        ->serializeToForum('tradeFeedbackNotice', 'prm-trade-feedback.notice'),
];
