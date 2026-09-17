<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $schema->create('trade_feedbacks', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('from_user_id');
            $table->unsignedInteger('to_user_id');
            $table->string('role', 16);
            $table->tinyInteger('rating');
            $table->string('short_comment', 80);
            $table->text('comment')->nullable();
            $table->string('thread_url', 255)->nullable();
            $table->timestamps();

            $table->foreign('from_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('to_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['to_user_id', 'created_at']);
            $table->index(['from_user_id', 'created_at']);
            $table->index(['to_user_id', 'rating']);
        });

        $schema->table('users', function (Blueprint $table) {
            $table->unsignedInteger('trade_received_count')->default(0);
            $table->unsignedInteger('trade_positive_count')->default(0);
            $table->unsignedInteger('trade_neutral_count')->default(0);
            $table->unsignedInteger('trade_negative_count')->default(0);
            $table->unsignedInteger('trade_given_positive_count')->default(0);
            $table->unsignedInteger('trade_given_neutral_count')->default(0);
            $table->unsignedInteger('trade_given_negative_count')->default(0);
        });
    },
    'down' => function (Builder $schema) {
        $schema->dropIfExists('trade_feedbacks');

        $schema->table('users', function (Blueprint $table) {
            $table->dropColumn([
                'trade_received_count',
                'trade_positive_count',
                'trade_neutral_count',
                'trade_negative_count',
                'trade_given_positive_count',
                'trade_given_neutral_count',
                'trade_given_negative_count',
            ]);
        });
    },
];
