<?php

namespace App\Models;

use Database\Factories\ExampleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 *
 *
 * @property int $id
 * @property int $definition_id
 * @property string $sentence
 * @method static \Database\Factories\ExampleFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Example newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Example newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Example query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Example whereDefinitionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Example whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Example whereSentence($value)
 * @mixin \Eloquent
 */
class Example extends Model
{
    /** @use HasFactory<ExampleFactory> */
    use HasFactory;

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
