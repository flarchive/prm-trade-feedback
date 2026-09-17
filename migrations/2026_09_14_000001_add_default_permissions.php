<?php

use Flarum\Database\Migration;
use Flarum\Group\Group;

return Migration::addPermissions([
    'user.giveTradeFeedback' => Group::MEMBER_ID,
    'user.moderateTradeFeedback' => Group::MODERATOR_ID,
]);
