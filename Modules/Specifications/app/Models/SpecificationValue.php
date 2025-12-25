<?php

namespace Modules\Specifications\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Cars\Models\Car;

// use Modules\Specifications\Database\Factories\SpecificationValueFactory;

class SpecificationValue extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */

    protected $fillable = ['specification_id', 'value'];
    protected $table="specification_values";

    public function specification()
    {
        return $this->belongsTo(Specification::class);
    }
}
