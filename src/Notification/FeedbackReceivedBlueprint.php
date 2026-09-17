<?php

namespace Prm\TradeFeedback\Notification;

use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\User\User;
use Prm\TradeFeedback\TradeFeedback;

class FeedbackReceivedBlueprint implements BlueprintInterface
{
    /**
     * @var TradeFeedback
     */
    public $feedback;

    public function __construct(TradeFeedback $feedback)
    {
        $this->feedback = $feedback;
    }

    public function getFromUser()
    {
        return $this->feedback->fromUser;
    }

    public function getSubject()
    {
        return $this->feedback->toUser;
    }

    public function getData()
    {
        return [
            'rating' => (int) $this->feedback->rating,
            'feedbackId' => $this->feedback->id,
        ];
    }

    public static function getType()
    {
        return 'tradeFeedbackReceived';
    }

    public static function getSubjectModel()
    {
        return User::class;
    }
}
