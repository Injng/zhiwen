<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property int $card_id
 * @property string $review_time
 * @property int $review_rating
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Log newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Log newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Log query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Log whereCardId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Log whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Log whereReviewRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Log whereReviewTime($value)
 * @mixin \Eloquent
 */
class Log extends Model
{
    /** @var array Attributes that are guarded (none, disable) */
    protected $guarded = [];

    /**
     * Disable timestamps for this model.
     */
    public $timestamps = false;
}
