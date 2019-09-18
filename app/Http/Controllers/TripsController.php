<?php

namespace App\Http\Controllers;

use App\Trip;
use Illuminate\Http\Request;

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

    public function store(Request $request)
    {
        //
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
