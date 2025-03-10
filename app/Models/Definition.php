<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property int $entry_id
 * @property string $part
 * @property string $definition
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Definition newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Definition newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Definition query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Definition whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Definition whereDefinition($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Definition whereEntryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Definition whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Definition wherePart($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Definition whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Definition extends Model
{
    //
}
