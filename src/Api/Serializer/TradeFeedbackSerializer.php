<?php

namespace Prm\TradeFeedback\Api\Serializer;

use Flarum\Api\Serializer\AbstractSerializer;
use Flarum\Api\Serializer\BasicUserSerializer;
use Prm\TradeFeedback\TradeFeedback;
use InvalidArgumentException;

class TradeFeedbackSerializer extends AbstractSerializer
{
    protected $type = 'trade-feedbacks';

    protected function getDefaultAttributes($feedback)
    {
        if (! ($feedback instanceof TradeFeedback)) {
            throw new InvalidArgumentException(
                get_class($this).' can only serialize instances of '.TradeFeedback::class
            );
        }

        $actor = $this->getActor();

        return [
            'role' => $feedback->role,
            'rating' => (int) $feedback->rating,
            'shortComment' => $feedback->short_comment,
            'comment' => $feedback->comment,
            'threadUrl' => $feedback->thread_url,
            'createdAt' => $this->formatDate($feedback->created_at),
            'canEdit' => $actor->can('edit', $feedback),
            'canDelete' => $actor->can('delete', $feedback),
        ];
    }

    protected function fromUser($feedback)
    {
        return $this->hasOne($feedback, BasicUserSerializer::class);
    }

    protected function toUser($feedback)
    {
        return $this->hasOne($feedback, BasicUserSerializer::class);
    }
}
