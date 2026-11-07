<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

class PushSubscriptionModel extends Model
{
    protected $table = 'push_subscriptions';

    protected $fillable = ['user_id', 'endpoint_hash', 'endpoint', 'p256dh', 'auth'];
}
