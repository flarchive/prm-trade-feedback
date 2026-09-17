<?php

namespace Prm\TradeFeedback;

use Carbon\Carbon;
use Flarum\Database\AbstractModel;
use Flarum\User\User;

/**
 * @property int $id
 * @property int $from_user_id
 * @property int $to_user_id
 * @property string $role
 * @property int $rating
 * @property string $short_comment
 * @property string|null $comment
 * @property string|null $thread_url
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property-read User $fromUser
 * @property-read User $toUser
 */
class TradeFeedback extends AbstractModel
{
    protected $table = 'trade_feedbacks';

    public $timestamps = true;

    protected $dates = ['created_at', 'updated_at'];

    protected $casts = [
        'rating' => 'integer',
    ];

    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser()
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }
}
