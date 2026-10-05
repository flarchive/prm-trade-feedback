<?php

namespace Prm\TradeFeedback\Api\Resource;

use Flarum\Api\Context as FlarumContext;
use Flarum\Api\Endpoint;
use Flarum\Api\Resource\AbstractDatabaseResource;
use Flarum\Api\Schema;
use Flarum\Api\Sort\SortColumn;
use Flarum\Foundation\ValidationException;
use Flarum\Notification\NotificationSyncer;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;
use Prm\TradeFeedback\Notification\FeedbackReceivedBlueprint;
use Prm\TradeFeedback\TradeFeedback;
use Prm\TradeFeedback\TradeStats;
use Tobyz\JsonApiServer\Context;

/**
 * @extends AbstractDatabaseResource<TradeFeedback>
 */
class TradeFeedbackResource extends AbstractDatabaseResource
{
    protected ?int $oldRating = null;

    public function __construct(
        protected NotificationSyncer $notifications
    ) {
    }

    public function type(): string
    {
        return 'trade-feedbacks';
    }

    public function model(): string
    {
        return TradeFeedback::class;
    }

    public function scope(Builder $query, Context $context): void
    {
        // Public trade feedback listing; filters restrict by user/direction/rating.
    }

    public function endpoints(): array
    {
        return [
            Endpoint\Index::make()
                ->defaultInclude(['fromUser', 'toUser'])
                ->defaultSort('-createdAt')
                ->paginate(10, 50),
            Endpoint\Create::make()
                ->authenticated()
                ->visible(fn (FlarumContext $context) => $context->getActor()->can('create', TradeFeedback::class))
                ->defaultInclude(['fromUser', 'toUser']),
            Endpoint\Update::make()
                ->authenticated()
                ->can('edit')
                ->defaultInclude(['fromUser', 'toUser']),
            Endpoint\Delete::make()
                ->authenticated()
                ->can('delete'),
        ];
    }

    public function fields(): array
    {
        return [
            Schema\Str::make('role')
                ->requiredOnCreate()
                ->writable()
                ->in(['buyer', 'seller', 'trade']),
            Schema\Integer::make('rating')
                ->requiredOnCreate()
                ->writable()
                ->in([-1, 0, 1]),
            Schema\Str::make('shortComment')
                ->requiredOnCreate()
                ->writable()
                ->minLength(1)
                ->maxLength(80),
            Schema\Str::make('comment')
                ->writable()
                ->nullable()
                ->maxLength(5000)
                ->set(function (TradeFeedback $feedback, ?string $value) {
                    $value = is_string($value) ? trim($value) : '';
                    $feedback->comment = $value !== '' ? $value : null;
                }),
            Schema\Str::make('threadUrl')
                ->writable()
                ->nullable()
                ->maxLength(255)
                ->set(function (TradeFeedback $feedback, ?string $value) {
                    $value = is_string($value) ? trim($value) : '';

                    if ($value !== '' && ! filter_var($value, FILTER_VALIDATE_URL)) {
                        throw new ValidationException(['threadUrl' => 'Invalid thread URL.']);
                    }

                    $feedback->thread_url = $value !== '' ? $value : null;
                }),
            Schema\DateTime::make('createdAt'),
            Schema\Boolean::make('canEdit')
                ->get(fn (TradeFeedback $feedback, FlarumContext $context) => $context->getActor()->can('edit', $feedback)),
            Schema\Boolean::make('canDelete')
                ->get(fn (TradeFeedback $feedback, FlarumContext $context) => $context->getActor()->can('delete', $feedback)),

            Schema\Relationship\ToOne::make('fromUser')
                ->type('users')
                ->includable(),
            Schema\Relationship\ToOne::make('toUser')
                ->type('users')
                ->includable()
                ->writable(fn (TradeFeedback $feedback, FlarumContext $context) => $context->creating())
                ->requiredOnCreate()
                ->set(function (TradeFeedback $feedback, User $toUser, FlarumContext $context) {
                    if ((int) $toUser->id === (int) $context->getActor()->id) {
                        throw new ValidationException([
                            'toUser' => 'You cannot leave trade feedback for yourself.',
                        ]);
                    }

                    $feedback->to_user_id = $toUser->id;
                }),
        ];
    }

    public function sorts(): array
    {
        return [
            SortColumn::make('createdAt'),
        ];
    }

    public function creating(object $model, Context $context): ?object
    {
        $model->from_user_id = $context->getActor()->id;

        return $model;
    }

    public function created(object $model, Context $context): ?object
    {
        TradeStats::apply($model, 1);

        $model->load(['fromUser', 'toUser']);
        $this->notifications->sync(new FeedbackReceivedBlueprint($model), [$model->toUser]);

        return $model;
    }

    public function updating(object $model, Context $context): ?object
    {
        $this->oldRating = (int) $model->rating;

        return $model;
    }

    public function updated(object $model, Context $context): ?object
    {
        if ($this->oldRating !== null && $this->oldRating !== (int) $model->rating) {
            $model->loadMissing(['fromUser', 'toUser']);
            TradeStats::changeRating($model->fromUser, $model->toUser, $this->oldRating, (int) $model->rating);
        }

        $this->oldRating = null;

        return $model;
    }

    public function deleting(object $model, Context $context): void
    {
        $model->load(['fromUser', 'toUser']);
        TradeStats::apply($model, -1);
        $this->notifications->sync(new FeedbackReceivedBlueprint($model), []);
    }
}
