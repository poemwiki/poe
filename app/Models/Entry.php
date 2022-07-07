<?php

namespace App\Models;

use App\Traits\RelatableNode;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Entry
 *
 * @property string $_name
 * @property string $_type
 * @property string $label
 * @package App
 */
class Entry extends Model{
    use RelatableNode;
    protected $table = 'entry';

    protected $fillable = [
        'type',
        'name',
    ];


    protected $dates = [
        'created_at',
        'updated_at',
    ];
}