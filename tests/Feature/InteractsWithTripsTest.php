<?php

namespace Tests\Feature;


use App\Utils\GpxConverter;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use phpGPX\phpGPX;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;

class InteractsWithTripsTest extends \TestCase
{
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = factory(\App\User::class)->create();
    }

    /** @test */
    function a_logged_user_can_list_his_trips()
    {
        $this->signIn($this->user);

        $trips = factory(\App\Trip::class, 3)->create(['user_id' => $this->user->getKey()]);

        $response = $this->get(route('trips.index'));

        foreach ($trips as $trip) {
            $response->seeJsonContains([
                'name' => $trip->name,
                'id' => $trip->id,
            ]);
        }
    }

    /** @test */
    function a_logged_user_can_not_see_someone_else_trips()
    {
        $this->signIn($this->user);

        $anotherUser = factory(\App\User::class)->create();

        factory(\App\Trip::class, 3)->create(['user_id' => $this->user->getKey()]);

        $anotherTrips = factory(\App\Trip::class, 3)->create(['user_id' => $anotherUser->getKey()]);

        $response = $this->get(route('trips.index'));

        foreach ($anotherTrips as $trip) {
            $response->dontSeeJson(['name' => $trip->name]);
        }
    }

    /** @test */
    function a_guest_can_not_access_trips()
    {
        $this->get(route('trips.index'))
            ->assertResponseStatus(401);
    }

    /** @test */
    function a_logged_user_can_create_trip()
    {
        $this->signIn($this->user);

        $response = $this->post(route('trips.store'), [
            'name' => 'My test trip',
            'trip' => UploadedFile::createFromBase(
                new SymfonyUploadedFile(
                    storage_path('example_trip.gpx'),
                    'trip.gpx'
                ),
                true
            )
        ]);

        $response->seeJson(['success' => 'Successfully saved trip!']);

        $this->seeInDatabase('trips', [
            'user_id' => $this->user->getKey()
        ]);
    }

    /** @test */
    function a_logged_user_can_not_create_trip_with_same_name()
    {
        $this->signIn($this->user);

        $firstResponse = $this->post(route('trips.store'), [
            'name' => 'My test trip',
            'trip' => UploadedFile::createFromBase(
                new SymfonyUploadedFile(
                    storage_path('example_trip.gpx'),
                    'trip.gpx'
                ),
                true
            )
        ]);

        $secondResponse = $this->post(route('trips.store'), [
            'name' => 'My test trip',
            'trip' => UploadedFile::createFromBase(
                new SymfonyUploadedFile(
                    storage_path('example_trip.gpx'),
                    'trip.gpx'
                ),
                true
            )
        ]);

        $this->seeJsonEquals(['name' => ['The name has already been taken.']]);
    }

    /** @test */
    function a_logged_user_can_see_his_saved_trip_represented_in_xml()
    {
        $this->signIn($this->user);

        $trip = factory(\App\Trip::class)->create(['user_id' => $this->user->id]);

        $trip->load(['waypoints', 'tracks', 'routes']);

        $gpxConverter = new GpxConverter(new Request, new phpGPX);

        $gpx = $gpxConverter->generateGpxFile($trip);

        $xml = $gpx->toXML()->saveXML();

        $this->get($trip->gpxPath())
            ->seeJson(['response' => $xml]);
    }

    /** @test */
    function a_logged_user_can_see_his_saved_trip_info()
    {
        $this->signIn($this->user);

        $trip = factory(\App\Trip::class)->create(['user_id' => $this->user->id]);

        $this->get($trip->path())
            ->seeJsonContains(['name' => $trip->name]);
    }

    /** @test */
    function a_logged_user_can_not_see_someone_else_trip_xml()
    {
        $this->signIn($this->user);

        $anotherUser = factory(\App\User::class)->create();

        $trip = factory(\App\Trip::class)->create(['user_id' => $anotherUser->id]);

        $this->get($trip->gpxPath())
            ->seeStatusCode(404);
    }

    /** @test */
    function a_logged_user_can_update_his_trip_name()
    {
        $this->signIn($this->user);

        $trip = factory(\App\Trip::class)->create(['user_id' => $this->user->id]);

        $modifiedTrip = factory(\App\Trip::class)->make(['user_id' => $this->user->id]);

        $request = $this->put(
            route('trips.update', ['id' => $trip]),
            ['name' => $modifiedTrip->name]
        );

        $request->seeJson(['success' => 'Trip successfully updated.']);
    }

    /** @test */
    function a_logged_user_can_not_update_trip_name_same_as_any_existing_trip_name()
    {
        $this->signIn($this->user);

        $trip = factory(\App\Trip::class)->create(['user_id' => $this->user->id]);
        $anotherTrip = factory(\App\Trip::class)->create(['user_id' => $this->user->id]);

        $request = $this->put(
            route('trips.update', ['id' => $trip]),
            ['name' => $anotherTrip->name]
        );

        $request->seeJsonEquals(['name' => ['The name has already been taken.']]);
    }

    /** @test */
    function a_logged_user_can_not_update_somebody_else_trip_name()
    {
        $this->signIn($this->user);

        $anotherUser = factory(\App\User::class)->create();

        $trip = factory(\App\Trip::class)->create(['user_id' => $anotherUser->id]);

        $request = $this->put(
            route('trips.update', ['id' => $trip]),
            ['name' => $trip->name]
        );

        $request->seeStatusCode(404);
    }

    /** @test */
    function a_logged_user_can_delete_his_trip()
    {
        $this->signIn($this->user);

        $trip = factory(\App\Trip::class)
            ->create(['user_id' => $this->user->id])
            ->load('tracks');

        $this->delete($trip->path())
            ->seeJson(['success' => 'Successfully deleted trip.']);


        $this->missingFromDatabase('trips', ['id' => $trip->id]);
        $this->missingFromDatabase('tracks', ['id' => $trip->tracks[0]->id]);
        $this->missingFromDatabase('points', ['id' => $trip->tracks[0]->points[0]->id]);
    }

    /** @test */
    function a_logged_user_can_not_delete_trip_by_another_owner()
    {
        $this->signIn($this->user);

        $anotherUser = factory(\App\User::class)->create();

        $trip = factory(\App\Trip::class)
            ->create(['user_id' => $anotherUser->id])
            ->load('tracks');

        $this->delete($trip->path())
            ->assertResponseStatus(404);

        $this->seeInDatabase('trips', ['id' => $trip->id]);
        $this->seeInDatabase('tracks', ['id' => $trip->tracks[0]->id]);
        $this->seeInDatabase('points', ['id' => $trip->tracks[0]->points[0]->id]);
    }
}