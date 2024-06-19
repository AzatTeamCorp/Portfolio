<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Support\Str;
use App\Models\PresentationFolder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Presentation extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'access_code',
        'join_url'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($presentation) {
            $presentation->join_url = static::generateRandomJoinUrl();
        });
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function folder() {
        return $this->belongsTo(PresentationFolder::class, 'folder_id');
    }

    public function slides()
    {
        return $this->hasMany(Slide::class);
    }

    protected static function generateRandomJoinUrl()
    {
        $joinUrl = '';
        do {
            $joinUrl = strtoupper(Str::random(rand(5, 8)));
        } while (static::where('join_url', $joinUrl)->exists());

        return $joinUrl;
    }

    public static function findByJoinUrl(string $joinUrl)
    {
        return static::where('join_url', $joinUrl)->first();
    }

    public function shows()
    {
        return $this->hasMany(Show::class);
    }


    public function show()
    {
        return $this->shows()->first();
    }

    public function resetResults()
    {
        $show = $this->show();
        if ($show) {
            $show->players()->delete();
            $show->answers()->delete();
            $show->delete();
        }
    }
}
