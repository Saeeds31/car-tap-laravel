<?php

namespace Modules\Receipt\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Users\Models\User;

class Receipt extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'image', 'status', 'message', 'amount'];
    protected $table = "receipts";
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
