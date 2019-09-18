<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Point extends Model
{
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'pointable_id',
        'pointable_type',
        'latitude',
        'longitude',
        'elevation',
        'time',
        'name',
        'description',
    ];

    /**
     * Get all of the owning pointable models.
     */
    public function pointable()
    {
        return $this->morphTo();
    }
}
