<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Route extends Model
{
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description'
    ];

    /**
     * The relations to eager load on every query.
     *
     * @var array
     */
    protected $with = ['points'];

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($track) {
            $track->points->each->delete();
        });
    }

    /**
     * Get all of the route's points.
     */
    public function points()
    {
        return $this->morphMany(Point::class, 'pointable');
    }
}
