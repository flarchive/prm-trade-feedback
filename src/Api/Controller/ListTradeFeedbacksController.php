<?php

namespace Prm\TradeFeedback\Api\Controller;

use Flarum\Api\Controller\AbstractListController;
use Illuminate\Support\Arr;
use Prm\TradeFeedback\Api\Serializer\TradeFeedbackSerializer;
use Prm\TradeFeedback\TradeFeedback;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ListTradeFeedbacksController extends AbstractListController
{
    public $serializer = TradeFeedbackSerializer::class;

    public $include = ['fromUser', 'toUser'];

    public $limit = 10;

    public $maxLimit = 50;

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $filters = $this->extractFilter($request);
        $limit = $this->extractLimit($request);
        $offset = $this->extractOffset($request);
        $include = $this->extractInclude($request);

        $userId = Arr::get($filters, 'user');
        $direction = Arr::get($filters, 'direction', 'received');
        $rating = Arr::get($filters, 'rating', 'all');

        $query = TradeFeedback::query()->orderByDesc('created_at');

        if ($direction === 'given') {
            $query->where('from_user_id', $userId);
        } else {
            $query->where('to_user_id', $userId);
        }

        if ($rating === 'positive') {
            $query->where('rating', 1);
        } elseif ($rating === 'neutral') {
            $query->where('rating', 0);
        } elseif ($rating === 'negative') {
            $query->where('rating', -1);
        }

        $total = (clone $query)->count();
        $results = $query->skip($offset)->take($limit)->get();

        $document->setMeta(['total' => $total]);

        $this->loadRelations($results, $include);

        return $results;
    }
}
