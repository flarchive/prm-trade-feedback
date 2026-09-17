<?php

namespace Prm\TradeFeedback\Api\Controller;

use Flarum\Api\Controller\AbstractDeleteController;
use Flarum\Http\RequestUtil;
use Flarum\Notification\NotificationSyncer;
use Illuminate\Support\Arr;
use Prm\TradeFeedback\Notification\FeedbackReceivedBlueprint;
use Prm\TradeFeedback\TradeFeedback;
use Prm\TradeFeedback\TradeStats;
use Psr\Http\Message\ServerRequestInterface;

class DeleteTradeFeedbackController extends AbstractDeleteController
{
    /**
     * @var NotificationSyncer
     */
    protected $notifications;

    public function __construct(NotificationSyncer $notifications)
    {
        $this->notifications = $notifications;
    }

    protected function delete(ServerRequestInterface $request)
    {
        $actor = RequestUtil::getActor($request);
        $id = Arr::get($request->getQueryParams(), 'id');
        $feedback = TradeFeedback::query()->findOrFail($id);

        $actor->assertCan('delete', $feedback);

        $feedback->load(['fromUser', 'toUser']);
        TradeStats::apply($feedback, -1);
        $this->notifications->sync(new FeedbackReceivedBlueprint($feedback), []);
        $feedback->delete();
    }
}
