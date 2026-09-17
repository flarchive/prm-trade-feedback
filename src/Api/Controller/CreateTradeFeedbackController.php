<?php

namespace Prm\TradeFeedback\Api\Controller;

use Flarum\Api\Controller\AbstractCreateController;
use Flarum\Foundation\ValidationException;
use Flarum\Http\RequestUtil;
use Flarum\Notification\NotificationSyncer;
use Flarum\User\User;
use Illuminate\Support\Arr;
use Prm\TradeFeedback\Api\Serializer\TradeFeedbackSerializer;
use Prm\TradeFeedback\Notification\FeedbackReceivedBlueprint;
use Prm\TradeFeedback\TradeFeedback;
use Prm\TradeFeedback\TradeStats;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class CreateTradeFeedbackController extends AbstractCreateController
{
    public $serializer = TradeFeedbackSerializer::class;

    public $include = ['fromUser', 'toUser'];

    /**
     * @var NotificationSyncer
     */
    protected $notifications;

    public function __construct(NotificationSyncer $notifications)
    {
        $this->notifications = $notifications;
    }

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertCan('create', TradeFeedback::class);

        $data = Arr::get($request->getParsedBody(), 'data', []);
        $attributes = Arr::get($data, 'attributes', []);
        $toUserId = Arr::get($data, 'relationships.toUser.data.id');

        $toUser = User::query()->findOrFail($toUserId);

        if ((int) $toUser->id === (int) $actor->id) {
            throw new ValidationException([
                'toUser' => 'You cannot leave trade feedback for yourself.',
            ]);
        }

        $feedback = new TradeFeedback();
        $feedback->from_user_id = $actor->id;
        $feedback->to_user_id = $toUser->id;
        $this->fill($feedback, $attributes);
        $feedback->save();

        TradeStats::apply($feedback, 1);

        $feedback->load(['fromUser', 'toUser']);
        $this->notifications->sync(new FeedbackReceivedBlueprint($feedback), [$toUser]);

        return $feedback;
    }

    public static function fill(TradeFeedback $feedback, array $attributes): void
    {
        $role = Arr::get($attributes, 'role');
        if (! in_array($role, ['buyer', 'seller', 'trade'], true)) {
            throw new ValidationException(['role' => 'Invalid role.']);
        }

        $rating = (int) Arr::get($attributes, 'rating');
        if (! in_array($rating, [-1, 0, 1], true)) {
            throw new ValidationException(['rating' => 'Invalid rating.']);
        }

        $short = trim((string) Arr::get($attributes, 'shortComment', ''));
        if ($short === '' || mb_strlen($short) > 80) {
            throw new ValidationException(['shortComment' => 'Short comment must be 1-80 characters.']);
        }

        $comment = Arr::get($attributes, 'comment');
        $comment = is_string($comment) ? trim($comment) : '';
        if (mb_strlen($comment) > 5000) {
            throw new ValidationException(['comment' => 'Comment is too long.']);
        }

        $threadUrl = Arr::get($attributes, 'threadUrl');
        $threadUrl = is_string($threadUrl) ? trim($threadUrl) : '';
        if ($threadUrl !== '' && ! filter_var($threadUrl, FILTER_VALIDATE_URL)) {
            throw new ValidationException(['threadUrl' => 'Invalid thread URL.']);
        }

        $feedback->role = $role;
        $feedback->rating = $rating;
        $feedback->short_comment = $short;
        $feedback->comment = $comment !== '' ? $comment : null;
        $feedback->thread_url = $threadUrl !== '' ? $threadUrl : null;
    }
}
