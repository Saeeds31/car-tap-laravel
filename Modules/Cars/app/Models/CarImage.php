<?php

namespace Modules\Cars\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Cars\Models\Car;

// use Modules\Products\Database\Factories\ProductImageFactory;

class CarImage extends Model
{
    use HasFactory;
    protected $fillable = ['car_id', 'path'];
    public function car()
    {
        return $this->belongsTo(Car::class);
    }
}
