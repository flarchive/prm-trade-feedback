<?php

namespace Prm\TradeFeedback\Api\Controller;

use Flarum\Api\Controller\AbstractShowController;
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Prm\TradeFeedback\Api\Serializer\TradeFeedbackSerializer;
use Prm\TradeFeedback\TradeFeedback;
use Prm\TradeFeedback\TradeStats;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class UpdateTradeFeedbackController extends AbstractShowController
{
    public $serializer = TradeFeedbackSerializer::class;

    public $include = ['fromUser', 'toUser'];

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $id = Arr::get($request->getQueryParams(), 'id');
        $feedback = TradeFeedback::query()->findOrFail($id);

        $actor->assertCan('edit', $feedback);

        $attributes = Arr::get($request->getParsedBody(), 'data.attributes', []);
        $oldRating = (int) $feedback->rating;

        CreateTradeFeedbackController::fill($feedback, array_merge([
            'role' => $feedback->role,
            'rating' => $feedback->rating,
            'shortComment' => $feedback->short_comment,
            'comment' => $feedback->comment,
            'threadUrl' => $feedback->thread_url,
        ], $attributes));

        $feedback->save();

        if ($oldRating !== (int) $feedback->rating) {
            TradeStats::changeRating($feedback->fromUser, $feedback->toUser, $oldRating, (int) $feedback->rating);
        }

        $feedback->load(['fromUser', 'toUser']);

        return $feedback;
    }
}
