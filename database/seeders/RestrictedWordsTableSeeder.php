<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RestrictedWord;

class RestrictedWordsTableSeeder extends Seeder
{
    public function run()
    {
        $words = [
            'contact',
            'contact number',
            'contact no',
            'contact info',
            'contact details',
            'phone',
            'phone number',
            'phone no',
            'mobile',
            'mobile number',
            'mobile no',
            'email',
            'email address',
            'email id',
            'address',
            'home address',
            'office address',
            'location',
            'location details',
            'phone contact',
            'mobile contact',
            'emergency contact',
            'fax',
            'fax number',
            'telephone',
            'telephone number',
            'cell phone',
            'cell number',
            'call me',
            'call us',
            'call',
            'text me',
            'text us',
            'sms',
            'messaging',
            'pager',
            'phone line',
            'landline',
            'whatsapp',
            'viber',
            'telegram',
            'snapchat',
            'skype',
            'zoom',
            'discord',
            'signal',
            'wechat',
            'line',
            'imessage',
            'facetime',
        ];

        foreach ($words as $word) {
            RestrictedWord::updateOrCreate(['word' => $word]);
        }
    }
}
