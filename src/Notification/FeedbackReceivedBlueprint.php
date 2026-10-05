<?php

namespace Prm\TradeFeedback\Notification;

use Flarum\Database\AbstractModel;
use Flarum\Notification\AlertableInterface;
use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\User\User;
use Prm\TradeFeedback\TradeFeedback;

class FeedbackReceivedBlueprint implements BlueprintInterface, AlertableInterface
{
    public function __construct(
        public TradeFeedback $feedback
    ) {
    }

    public function getFromUser(): ?User
    {
        return $this->feedback->fromUser;
    }

    public function getSubject(): ?AbstractModel
    {
        return $this->feedback->toUser;
    }

    public function getData(): mixed
    {
        return [
            'rating' => (int) $this->feedback->rating,
            'feedbackId' => $this->feedback->id,
        ];
    }

    public static function getType(): string
    {
        return 'tradeFeedbackReceived';
    }

    public static function getSubjectModel(): string
    {
        return User::class;
    }
}
