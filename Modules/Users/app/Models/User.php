<?php

namespace Modules\Users\Models;

use Illuminate\Support\Str;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Modules\Wallet\Models\Wallet;
use Laravel\Sanctum\HasApiTokens;
use Modules\Locations\Models\City;

class User extends Authenticatable
{
    use HasFactory, HasApiTokens, Notifiable;

    protected $fillable = [
        'full_name',
        'mobile',
        'national_code',
        'birth_date',
        'birth_certificate_number',
        'marital_status',
        'image',
        'address',
        'city_id',
        'postal_code',
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];

    /**
     * Get all addresses for the user.
     */
    public function city()
    {
        return $this->belongsTo(City::class);
    }




    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }
    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }
    public function getPermissionsAttribute()
    {
        return $this->roles
            ->map->permissions
            ->flatten()
            ->pluck('name')
            ->unique()
            ->values()
            ->toArray();
    }

    public function hasPermission($permission)
    {
        return $this->permissions()->contains('name', $permission);
    }
    public static  function dashboardReport()
    {
        $todayUsers = self::whereDate('created_at', today())->pluck('id');
        return [
            'total_users'       => self::count(),
            'today_registered'  => $todayUsers->count(),
        ];
    }
}
