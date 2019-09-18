<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'name',
        'creator',
        'metadata',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Get the tracks for the trip.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tracks()
    {
        return $this->hasMany(Track::class);
    }
    /**
     * Get the routes for the trip.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function routes()
    {
        return $this->hasMany(Route::class);
    }
    /**
     * Get all of the trip's points as waypoints.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function waypoints()
    {
        return $this->morphMany(Point::class, 'pointable');
    }

    /**
     * Return resource url path.
     *
     * @return string
     */
    public function path()
    {
        return route('trips.show', $this);
    }

    /**
     * Return gpx xml resource.
     *
     * @return string
     */
    public function gpxPath()
    {
        return route('trips.gpx', $this);
    }
}
