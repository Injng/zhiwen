<?php

namespace App\Models;

use Database\Factories\DefinitionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property int $entry_id
 * @property string $part
 * @property string $definition
 * @method static \Database\Factories\DefinitionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Definition newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Definition newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Definition query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Definition whereDefinition($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Definition whereEntryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Definition whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Definition wherePart($value)
 * @mixin \Eloquent
 */
class Definition extends Model
{
    /** @use HasFactory<DefinitionFactory> */
    use HasFactory;

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
