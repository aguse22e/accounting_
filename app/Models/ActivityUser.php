<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'model_name',
        'model_id',
        'old_data',
        'new_data',
        'users'
    ];

    protected $casts = [
        'old_data' => 'array',
        'new_data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'Id_User');
    }

    /**
     * Log user activity
     */
    public static function logActivity($action, $modelName, $modelId, $oldData = null, $newData = null, $userId = null)
    {
        $userId = $userId ?? auth()->id();
        
        return self::create([
            'user_id' => $userId,
            'action' => strtoupper($action),
            'model_name' => $modelName,
            'model_id' => $modelId,
            'old_data' => $oldData,
            'new_data' => $newData,
            'users' => request()->userAgent()
        ]);
    }
}
