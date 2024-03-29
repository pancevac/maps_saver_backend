<?php

namespace App;

use App\Scopes\OwnerScope;
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
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new OwnerScope);

        static::deleting(function ($trip) {
            $trip->tracks->each->delete();
            $trip->routes->each->delete();
            $trip->waypoints->each->delete();
        });
    }

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
        return route('trips.show', ['id' => $this]);
    }

    /**
     * Return gpx xml resource.
     *
     * @return string
     */
    public function gpxPath()
    {
        return route('trips.gpx', ['id' => $this]);
    }

    /**
     * Create tracks with the points for the trip.
     *
     * @param array $tracks
     */
    public function createTracks(array $tracks)
    {
        $savedTracks = $this->tracks()->createMany($tracks);

        $savedTracks->each(function (Track $track, int $key) use ($tracks) {
            $track->points()->createMany($tracks[$key]['points']);
        });
    }

    /**
     * Create routes with the points for the trip.
     *
     * @param array $routes
     */
    public function createRoutes(array $routes)
    {
        $savedRoutes = $this->routes()->createMany($routes);

        $savedRoutes->each(function (Route $route, int $key) use ($routes) {
            $route->points()->createMany($routes[$key]['points']);
        });
    }

    /**
     * Create waypoints for the trip.
     *
     * @param array $waypoints
     */
    public function createWaypoints(array $waypoints)
    {
        $this->waypoints()->createMany($waypoints);
    }
}
