<?php

namespace Prm\TradeFeedback\Access;

use Flarum\User\Access\AbstractPolicy;
use Flarum\User\User;
use Prm\TradeFeedback\TradeFeedback;

class FeedbackPolicy extends AbstractPolicy
{
    public function create(User $actor)
    {
        return $actor->hasPermission('user.giveTradeFeedback');
    }

    public function edit(User $actor, TradeFeedback $feedback)
    {
        return $actor->id === $feedback->from_user_id || $actor->hasPermission('user.moderateTradeFeedback');
    }

    public function delete(User $actor, TradeFeedback $feedback)
    {
        return $this->edit($actor, $feedback);
    }
}
