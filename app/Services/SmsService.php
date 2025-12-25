<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SmsService
{
    public function sendWelcome($mobile)
    {
        $message = base64_encode("ضمن تشکر از حسن انتخاب شما\nثبت نام شما با موفقیت انجام شد\تکین آرتا پرگاس ");

        return Http::get("https://api.kavenegar.com/v1/71705A6858737476417466345933356B3578614C44396154756F6B384833424A3646674B4F4F585577764D3D/sms/send.json", [
            'receptor' => $mobile,
            'message' => $message,
            'sender' => '1000066006700'
        ]);
    }

    public function sendText($mobile, $text)
    {

        return Http::get("https://api.kavenegar.com/v1/71705A6858737476417466345933356B3578614C44396154756F6B384833424A3646674B4F4F585577764D3D/sms/send.json", [
            'receptor' => $mobile,
            'message' => $text,
            'sender' => '1000066006700'
        ]);
    }
}
