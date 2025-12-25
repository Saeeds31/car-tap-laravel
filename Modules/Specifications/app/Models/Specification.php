<?php

namespace Modules\Specifications\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Cars\Models\Car;

// use Modules\Specifications\Database\Factories\SpecificationFactory;

class Specification extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['title', 'group_id'];

    public function values()
    {
        return $this->hasMany(SpecificationValue::class);
    }
    public function group()
    {
        return $this->belongsTo(SpecificationGroup::class);
    }
    public function cars()
    {
        return $this->belongsToMany(Car::class, 'car_specification')
            ->withPivot('specification_value_id')
            ->withTimestamps();
    }
}
