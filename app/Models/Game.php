<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'provider_id',
        'category',
        'image',
        'theoretical_rtp',
        'current_rtp',
        'daily_rtp',
        'weekly_rtp',
        'monthly_rtp',
        'hit_ratio',
        'risk_level',
        'paylines',
        'min_bet',
        'max_bet',
        'last_updated',
        'is_trending',
        'is_popular',
        'is_hot'
    ];

    protected $casts = [
        'theoretical_rtp' => 'float',
        'current_rtp' => 'float',
        'daily_rtp' => 'float',
        'weekly_rtp' => 'float',
        'monthly_rtp' => 'float',
        'hit_ratio' => 'float',
        'min_bet' => 'float',
        'max_bet' => 'float',
        'last_updated' => 'datetime',
        'is_trending' => 'boolean',
        'is_popular' => 'boolean',
        'is_hot' => 'boolean'
    ];

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }
}
