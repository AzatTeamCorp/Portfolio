<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Player extends Authenticatable
{
    protected $table = 'show_players';
    protected $guarded = ['id'];

    public function show()
    {
        return $this->belongsTo(Show::class);
    }

    public function answers()
    {
        return $this->hasMany(PlayerAnswer::class,  'show_player_id');
    }

    public function totalPoints()
    {
        return $this->answers->sum(function ($answer) {
            return $answer->calcPoints();
        });
    }
}
