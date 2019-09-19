<?php


namespace App\Utils;

use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use phpGPX\Models\GpxFile;
use phpGPX\Models\Link;
use phpGPX\Models\Metadata;
use phpGPX\Models\Point;
use phpGPX\Models\Route;
use phpGPX\Models\Segment;
use phpGPX\Models\Track;
use phpGPX\phpGPX;

class GpxConverter
{
    /**
     * @var array|Request|string
     */
    protected $request;

    /**
     * @var phpGPX
     */
    protected $gpxPackage;

    /**
     * @var GpxFile
     */
    protected $parsedGpx;

    /**
     * GpxConverter constructor.
     *
     * @param Request|null $request
     * @param phpGPX $phpGPX
     */
    public function __construct(Request $request, phpGPX $phpGPX)
    {
        $this->request = $request;
        $this->gpxPackage = $phpGPX;
    }

    /**
     * Load and parse gpx file.
     *
     * @return $this
     */
    public function load()
    {
        $this->parsedGpx = $this->gpxPackage->load(
            //$this->request->file('trip')->getRealPath()
            $this->request->trip->getRealPath()
        );

        return $this;
    }

    /**
     * Return parsed creator of file if any.
     *
     * @return string
     */
    public function getCreator(): string
    {
        if (!$this->parsedGpx instanceof GpxFile || !$this->parsedGpx->creator) {
            return '';
        }

        return $this->parsedGpx->creator;
    }

    /**
     * Return parsed metadata as array if any.
     *
     * @return array
     */
    public function getMetaData(): array
    {
        if (!$this->parsedGpx instanceof GpxFile || !$this->parsedGpx->metadata) {
            return [];
        }

        return $this->parsedGpx->metadata->toArray();
    }

    /**
     * Retrieve tracks array with points.
     *
     * @return array
     */
    public function getTracks()
    {
        $tracks = $this->parsedGpx->tracks;

        return array_map(function (Track $track) {
            return [
                'name' => $track->name,
                'description' => $track->description,
                'points' => $this->mapPoints($track->getPoints()),
            ];
        }, $tracks);
    }

    /**
     * Retrieve routes array with points.
     *
     * @return array
     */
    public function getRoutes()
    {
        $routes = $this->parsedGpx->routes;

        return array_map(function (Route $route) {
            return [
                'name' => $route->name,
                'description' => $route->description,
                'points' => $this->mapPoints($route->getPoints()),
            ];
        }, $routes);
    }

    /**
     * Retrieve waypoints array as points.
     *
     * @return array
     */
    public function getWaypoints()
    {
        $waypoints =  $this->parsedGpx->waypoints;

        return $this->mapPoints($waypoints);
    }

    /**
     * Map points array of objects to new array.
     *
     * @param array $points
     * @return array
     */
    protected function mapPoints(array $points): array
    {
        return array_map(function (Point $point) {
            return [
                'latitude' => $point->latitude,
                'longitude' => $point->longitude,
                'elevation' => $point->elevation,
                'time' => $point->time,
                'name' => $point->name,
                'description' => $point->description,
            ];
        }, $points);
    }

    /**
     * Return gpx object with hydrated data.
     *
     * @param \App\Trip $trip
     * @return GpxFile
     */
    public function generateGpxFile(\App\Trip $trip)
    {
        $gpxFile = new GpxFile();

        $trip->load(['tracks', 'routes', 'waypoints']);

        $gpxFile->creator       = $trip->creator;
        //$gpxFile->metadata      = $this->composeMetadata($trip->metadata ?? []);
        $gpxFile->tracks        = $this->composeTracks($trip->tracks);
        $gpxFile->waypoints     = $this->composeWaypoints($trip->waypoints);
        $gpxFile->routes        = $this->composeRoute($trip->routes);

        return $gpxFile;
    }

    /**
     * Compose tracks with segment and its points(track-points).
     *
     * @param Collection $tracks
     * @return array
     */
    protected function composeTracks(Collection $tracks): array
    {
        return $tracks->map(function (\App\Track $trackModel) {

            $segment = new Segment();
            $track = new Track();

            $points = $this->composePoint($trackModel->points, Point::TRACKPOINT);

            $segment->points = $points;
            $track->segments[] = $segment;

            return $track;

        })->toArray();
    }

    /**
     * Compose route with its point(route-points).
     *
     * @param Collection $routes
     * @return array
     */
    protected function composeRoute(Collection $routes): array
    {
        return $routes->map(function (\App\Route $routeModel) {

            $route = new Route();

            $route->points = $this->composePoint($routeModel->points, Point::ROUTEPOINT);

            return $route;

        })->toArray();
    }

    /**
     * Compose waypoints.
     *
     * @param Collection $waypoints
     * @return array
     */
    protected function composeWaypoints(Collection $waypoints): array
    {
        return $this->composePoint($waypoints, Point::WAYPOINT);
    }

    /**
     * Compose points.
     *
     * @param Collection $pointCollection
     * @param string $pointType
     * @return array
     */
    protected function composePoint(Collection $pointCollection, string $pointType): array
    {
        return $pointCollection->map(function (\App\Point $pointModel) use ($pointType) {
            $point 	= new Point($pointType);
            $point->latitude 			= $pointModel->latitude;
            $point->longitude 			= $pointModel->longitude;
            $point->elevation 			= $pointModel->elevation;
            $point->time 				= new DateTime($pointModel->time);
            $point->name                = $pointModel->name;
            $point->description         = $pointModel->description;

            return $point;
        })->toArray();
    }

    /**
     * Composer metadata from $meta array.
     *
     * @param array $meta
     * @return Metadata
     */
    protected function composeMetadata(array $meta = []): Metadata
    {
        $metadata = new Metadata();

        foreach ($meta as $attribute => $value) {

            if ($attribute == 'links') {
                $linkObjs = [];
                foreach ($value as $key => $link) {

                    $linkObjs[$key] = new Link();

                    foreach (array_keys($link) as $property) {;
                        $linkObjs[$key]->$property = $value[$key][$property];
                    }
                }
                $metadata->$attribute = $linkObjs;

            } elseif (is_string($value)) {

                $metadata->$attribute = $value;

            } elseif (is_array($value)) {

                $name = ucfirst($attribute);
                $metaObject = new $name;

                foreach ($value as $propertyName => $propertyValue) {
                    $metaObject->$propertyName = $propertyValue;
                }

                $metadata->$attribute = $metaObject;
            }
        }

        return $metadata;
    }
}