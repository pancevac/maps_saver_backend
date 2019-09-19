<?php

namespace App\Http\Controllers;

use App\Rules\IsGpxFile;
use App\Trip;
use App\Utils\GpxConverter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TripsController extends Controller
{
    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        return response()->json([
            'trips' => Trip::latest()->get()
        ]);
    }

    /**
     * @param int $id
     * @return mixed
     */
    public function show(int $id)
    {
        return Trip::findOrFail($id);
    }

    /**
     * @param int $id
     * @param GpxConverter $gpxConverter
     * @return array
     */
    public function getGpx(int $id, GpxConverter $gpxConverter)
    {
        $trip = Trip::findOrFail($id);

        return [
            'response' => $gpxConverter
                ->generateGpxFile($trip)
                ->toXML()
                ->saveXML()
        ];
    }

    /**
     * Handle validation and uploading gpx file, trip.
     *
     * @param Request $request
     * @param GpxConverter $gpxConverter
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request, GpxConverter $gpxConverter)
    {
        $this->validate($request, [
            'name' => ['required', 'string', 'max:255', Rule::unique('trips', 'name')->where('user_id', Auth::id())],
            'trip' => ['required', new IsGpxFile]
        ]);

        // Try to load coordinates from gpx file
        try {
            $gpxConverter->load();
        } catch (\Exception $e) {
            return back()->withErrors(['GPX', 'Corrupted file.']);
        }

        // create single trip
        $trip = Trip::create([
            'user_id' => Auth::id(),
            'name' => $request->name,
            'creator' => $gpxConverter->getCreator(),
            'metadata' => $gpxConverter->getMetaData(),
        ]);

        // create tracks, routes and waypoints for trip
        $trip->createTracks($gpxConverter->getTracks());
        $trip->createRoutes($gpxConverter->getRoutes());
        $trip->createWaypoints($gpxConverter->getWaypoints());

        return response()->json(['success' => 'Successfully saved trip!']);
    }

    public function update($id, Request $request)
    {
        //
    }

    /**
     * @param int $id
     * @return \Illuminate\Http\JsonResponse|void
     */
    public function destroy(int $id)
    {
        // Trip is protected by deleting from another user
        // by global scope OwnerScope
        if (Trip::destroy($id)) {
            return response()->json(['success' => 'Successfully deleted trip.']);
        }

        return abort(404);
    }
}
