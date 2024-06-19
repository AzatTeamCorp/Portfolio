<?php

namespace App\Models;

use App\Models\Slide;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SlideTheme extends Model
{
    use HasFactory;

    public $timestamps = false;

    public function slides()
    {
        return $this->hasMany(Slide::class);
    }
}
