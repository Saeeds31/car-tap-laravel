<?php

namespace Modules\CarRequest\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\CarRequest\Database\Factories\CarRequestFactory;

class CarRequest extends Model
{
    use HasFactory;
    protected $fillable = [];
    protected $table = "car_requests";
}
