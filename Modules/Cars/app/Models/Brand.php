<?php

namespace Modules\Cars\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Cars\Database\Factories\BrandFactory;

class Brand extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['title', 'image', 'description','show_in_home'];

    // protected static function newFactory(): BrandFactory
    // {
    //     // return BrandFactory::new();
    // }
    public function cars()
    {
        return $this->hasMany(Car::class);
    }
}
