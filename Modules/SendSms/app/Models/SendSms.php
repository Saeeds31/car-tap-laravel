<?php

namespace Modules\SendSms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Users\Models\Role;
use Modules\Users\Models\User;

// use Modules\SendSms\Database\Factories\SendSmsFactory;

class SendSms extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'subject',
        'sender_id',
        'role_id',
        'body'
    ];
    protected $table = 'send_sms';
    public function sender()
    {
        return $this->belongsTo(User::class);
    }
    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}
