<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SmsService
{
    public function sendWelcome($mobile)
    {
        $message = base64_encode("ضمن تشکر از حسن انتخاب شما\nثبت نام شما با موفقیت انجام شد\تکین آراز پرگاس ");

        return Http::get("https://api.kavenegar.com/v1/2B456D34746B54555A55796D5542655A694E693753694D2B47524C6E4F556A69584551735A7143346357733D/sms/send.json", [
            'receptor' => $mobile,
            'message' => $message,
            'sender' => '1000066006700'
        ]);
    }

    public function sendText($mobile, $text)
    {

        return Http::get("https://api.kavenegar.com/v1/2B456D34746B54555A55796D5542655A694E693753694D2B47524C6E4F556A69584551735A7143346357733D/sms/send.json", [
            'receptor' => $mobile,
            'message' => $text,
            'sender' => '1000066006700'
        ]);
    }
}
