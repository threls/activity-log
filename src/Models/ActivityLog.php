<?php

namespace Threls\ThrelsActivityLog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $table = 'activity_log';

    protected $guarded = ['id'];

    protected $casts = [
        'data' => 'array',
        'dirty_keys' => 'array',
    ];

    private mixed $userInstance = "\App\Models\User";

    public function __construct()
    {
        parent::__construct();
        $userInstance = config('activity-log.user_model');
        if (! empty($userInstance)) {
            $this->userInstance = $userInstance;
        }
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo($this->userInstance);
    }
}
