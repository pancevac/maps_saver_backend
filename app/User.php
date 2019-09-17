<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Illuminate\Support\Facades\Hash;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Laravel\Passport\HasApiTokens;
use League\OAuth2\Server\Exception\OAuthServerException;

class User extends Model implements AuthenticatableContract, AuthorizableContract
{
    use HasApiTokens,
        Authenticatable,
        Authorizable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'activated', 'blocked'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Validate if user entered matching password, is activated and not blocked.
     *
     * @see \Laravel\Passport\Bridge\UserRepository @ method getUserEntityByUserCredentials
     *
     * @param $password
     * @return bool
     * @throws OAuthServerException
     */
    public function validateForPassportPasswordGrant($password)
    {
        //check for password
        if (Hash::check($password, $this->getAuthPassword())) {

            //is user active?
            if ($this->activated && !$this->blocked) {
                return true;
            }

            throw new OAuthServerException(
                'User account is not active or is blocked', 6,
                'account_inactive', 401);
        }
    }
}
