<?php

namespace Modules\Cars\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Cars\Models\CarImage;
use Modules\Specifications\Models\Specification;

// use Modules\Cars\Database\Factories\CarsFactory;

class Car extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'price',
        'min_order_count',
        'description',
        'image',
        'brand_id',
        'category_id'
    ];
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function images()
    {
        return $this->hasMany(CarImage::class);
    }

    public function specifications()
    {
        return $this->belongsToMany(Specification::class, 'car_specification')
            ->withPivot('specification_value_id')
            ->withTimestamps();
    }
    public static function dashboardReport()
    {
        return [
            'total_cars' => self::count(),
            'cars_by_brand' => self::selectRaw('brand_id, COUNT(*) as total')
                ->groupBy('brand_id')
                ->pluck('total', 'brand_id'),

            'cars_by_category' => self::selectRaw('category_id, COUNT(*) as total')
                ->groupBy('category_id')
                ->pluck('total', 'category_id'),

            'average_price' => self::avg('price'),
            'min_price' => self::min('price'),
            'max_price' => self::max('price'),
        ];
    }
}
