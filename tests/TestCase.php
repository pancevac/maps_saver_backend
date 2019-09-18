<?php

use App\User;
use \Laravel\Lumen\Testing\DatabaseMigrations;

abstract class TestCase extends Laravel\Lumen\Testing\TestCase
{
    use DatabaseMigrations;

    /**
     * Creates the application.
     *
     * @return \Laravel\Lumen\Application
     */
    public function createApplication()
    {
        return require __DIR__.'/../bootstrap/app.php';
    }

    /**
     * Sign in a user.
     *
     * @param User|null $user
     * @return $this
     */
    protected function signIn(User $user = null): self
    {
        $user = $user ?? factory(User::class)->create();

        $this->actingAs($user);

        return $this;
    }
}
