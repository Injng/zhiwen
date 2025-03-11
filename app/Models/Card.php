<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property int $entry_id
 * @property string $due
 * @property int $stability
 * @property int $difficulty
 * @property int $elapsed_days
 * @property int $scheduled_days
 * @property int $reps
 * @property int $lapses
 * @property string $state
 * @property string|null $last_review
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Card newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Card newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Card query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Card whereDifficulty($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Card whereDue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Card whereElapsedDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Card whereEntryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Card whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Card whereLapses($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Card whereLastReview($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Card whereReps($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Card whereScheduledDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Card whereStability($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Card whereState($value)
 * @mixin \Eloquent
 */
class Card extends Model
{
    /** @var array Attributes that are guarded (none, disable) */
    protected $guarded = [];

    /**
     * Disable timestamps for this model.
     */
    public $timestamps = false;
}
