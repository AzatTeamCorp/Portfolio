<?php

namespace App\Models;

use App\Models\Slide;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PlayerAnswer extends Model
{
    protected $guarded = ['id'];

    public function slide()
    {
        return $this->belongsTo(Slide::class);
    }

    public function calcPoints()
    {
        $points = 0;
        $slide = $this->slide;

        if ($slide->correctAnswer() == $this->answer) {
            $settings = $slide->settings;
            $answer_time = $this->answer_time;
            $points_max = $settings['points_max'];
            $points_min = $settings['points_min'];
            $time_limit = $settings['time_limit'];

            $points_per_second = ($points_max - $points_min) / $time_limit;
            $points = $points_max - $points_per_second * $answer_time;
            $points = max($points, $points_min);
        }

        return $points;
    }
}
