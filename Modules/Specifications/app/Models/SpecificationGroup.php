<?php

namespace Modules\Specifications\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Specifications\Database\Factories\SpecificationGroupFactory;

class SpecificationGroup extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['title'];

    protected $table = "specification_group";
    public function specifications()
    {
        return $this->hasMany(Specification::class, 'group_id');
    }
}
