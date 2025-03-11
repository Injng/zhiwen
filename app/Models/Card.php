<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Card extends Model
{
    /** @var array Attributes that are guarded (none, disable) */
    protected $guarded = [];

    /**
     * Disable timestamps for this model.
     */
    public $timestamps = false;
}
