<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Definition extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'entry_id',
        'part',
        'definition'
    ];

    /**
     * Disable timestamps for this model.
     */
    public $timestamps = false;
}
