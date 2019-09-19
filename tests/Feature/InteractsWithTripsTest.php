<?php

namespace Tests\Feature;


use Illuminate\Http\UploadedFile;
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
}