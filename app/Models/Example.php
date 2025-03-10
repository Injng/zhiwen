<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Example extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'definition_id',
        'sentence'
    ];

    /**
     * Disable timestamps for this model.
     */
    public $timestamps = false;
}
