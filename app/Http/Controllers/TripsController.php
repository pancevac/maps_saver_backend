<?php

namespace App\Http\Controllers;

use App\Rules\IsGpxFile;
use App\Trip;
use App\Utils\GpxConverter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

    public function show($id)
    {
        //
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
            'name' => ['required', 'string', 'max:255'],
            'trip' => ['required', new IsGpxFile]
        ]);

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

    public function destroy($id)
    {
        //
    }
}
