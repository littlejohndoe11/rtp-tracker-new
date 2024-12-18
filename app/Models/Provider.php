<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Provider extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'logo',
        'is_active'
    ];

    public function games()
    {
        return $this->hasMany(Game::class);
    }
}
