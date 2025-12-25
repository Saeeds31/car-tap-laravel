<?php

namespace Modules\Specifications\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Cars\Models\Car;

// use Modules\Specifications\Database\Factories\ProductSpecificationValueFactory;

class CarSpecification extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['car_id', 'specification_id', 'specification_value_id'];
    protected $table = "car_specification";

    public function specification()
    {
        return $this->belongsTo(Specification::class);
    }

    public function specificationValue()
    {
        return $this->belongsTo(SpecificationValue::class);
    }

    public function car()
    {
        return $this->belongsTo(Car::class);
    }
}
