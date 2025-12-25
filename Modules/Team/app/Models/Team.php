<?php

namespace Modules\Team\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Team\Database\Factories\TeamFactory;

class Team extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'full_name',
        'image',
        'post',
        'sort_order'
    ];
    protected $table = 'team';

    // protected static function newFactory(): TeamFactory
    // {
    //     // return TeamFactory::new();
    // }
}
