<?php

namespace Prm\TradeFeedback;

use Carbon\Carbon;
use Flarum\User\User;

class TradeStats
{
    const RATING_POSITIVE = 1;
    const RATING_NEUTRAL = 0;
    const RATING_NEGATIVE = -1;

    public static function apply(TradeFeedback $feedback, int $delta): void
    {
        TradeFeedback::query()->getConnection()->transaction(function () use ($feedback, $delta) {
            $from = User::query()->lockForUpdate()->find($feedback->from_user_id);
            $to = User::query()->lockForUpdate()->find($feedback->to_user_id);

            if (! $from || ! $to) {
                return;
            }

            $to->trade_received_count = max(0, (int) $to->trade_received_count + $delta);

            if ((int) $feedback->rating === self::RATING_POSITIVE) {
                $to->trade_positive_count = max(0, (int) $to->trade_positive_count + $delta);
                $from->trade_given_positive_count = max(0, (int) $from->trade_given_positive_count + $delta);
            } elseif ((int) $feedback->rating === self::RATING_NEUTRAL) {
                $to->trade_neutral_count = max(0, (int) $to->trade_neutral_count + $delta);
                $from->trade_given_neutral_count = max(0, (int) $from->trade_given_neutral_count + $delta);
            } else {
                $to->trade_negative_count = max(0, (int) $to->trade_negative_count + $delta);
                $from->trade_given_negative_count = max(0, (int) $from->trade_given_negative_count + $delta);
            }

            $from->save();
            $to->save();
        });
    }

    public static function changeRating(User $from, User $to, int $oldRating, int $newRating): void
    {
        if ($oldRating === $newRating) {
            return;
        }

        TradeFeedback::query()->getConnection()->transaction(function () use ($from, $to, $oldRating, $newRating) {
            $from = User::query()->lockForUpdate()->find($from->id);
            $to = User::query()->lockForUpdate()->find($to->id);

            self::adjustGiven($from, $oldRating, -1);
            self::adjustReceived($to, $oldRating, -1);
            self::adjustGiven($from, $newRating, 1);
            self::adjustReceived($to, $newRating, 1);

            $from->save();
            $to->save();
        });
    }

    public static function forUser(User $user): array
    {
        $positive = (int) $user->trade_positive_count;
        $negative = (int) $user->trade_negative_count;
        $rated = $positive + $negative;

        return [
            'received' => (int) $user->trade_received_count,
            'percent' => $rated > 0 ? round(($positive / $rated) * 100, 1) : 0,
            'receivedPositive' => $positive,
            'receivedNeutral' => (int) $user->trade_neutral_count,
            'receivedNegative' => $negative,
            'givenPositive' => (int) $user->trade_given_positive_count,
            'givenNeutral' => (int) $user->trade_given_neutral_count,
            'givenNegative' => (int) $user->trade_given_negative_count,
            'periods' => [
                '30d' => self::periodCounts($user, Carbon::now()->subDays(30)),
                '6m' => self::periodCounts($user, Carbon::now()->subMonths(6)),
                '1y' => self::periodCounts($user, Carbon::now()->subYear()),
            ],
        ];
    }

    protected static function periodCounts(User $user, Carbon $since): array
    {
        $rows = TradeFeedback::query()
            ->where('to_user_id', $user->id)
            ->where('created_at', '>=', $since)
            ->selectRaw('rating, COUNT(*) as aggregate')
            ->groupBy('rating')
            ->pluck('aggregate', 'rating');

        $positive = (int) ($rows[self::RATING_POSITIVE] ?? 0);
        $neutral = (int) ($rows[self::RATING_NEUTRAL] ?? 0);
        $negative = (int) ($rows[self::RATING_NEGATIVE] ?? 0);
        $rated = $positive + $negative;

        return [
            'positive' => $positive,
            'neutral' => $neutral,
            'negative' => $negative,
            'percent' => $rated > 0 ? (int) round(($positive / $rated) * 100) : 0,
        ];
    }

    protected static function adjustGiven(User $user, int $rating, int $delta): void
    {
        if ($rating === self::RATING_POSITIVE) {
            $user->trade_given_positive_count = max(0, (int) $user->trade_given_positive_count + $delta);
        } elseif ($rating === self::RATING_NEUTRAL) {
            $user->trade_given_neutral_count = max(0, (int) $user->trade_given_neutral_count + $delta);
        } else {
            $user->trade_given_negative_count = max(0, (int) $user->trade_given_negative_count + $delta);
        }
    }

    protected static function adjustReceived(User $user, int $rating, int $delta): void
    {
        if ($rating === self::RATING_POSITIVE) {
            $user->trade_positive_count = max(0, (int) $user->trade_positive_count + $delta);
        } elseif ($rating === self::RATING_NEUTRAL) {
            $user->trade_neutral_count = max(0, (int) $user->trade_neutral_count + $delta);
        } else {
            $user->trade_negative_count = max(0, (int) $user->trade_negative_count + $delta);
        }
    }
}
