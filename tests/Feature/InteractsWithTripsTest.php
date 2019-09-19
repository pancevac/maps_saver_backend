<?php

namespace Tests\Feature;


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
}