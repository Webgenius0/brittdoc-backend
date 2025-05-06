<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Faq;

class FaqSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faqs = [
            [
                'question' => 'How do I reset my password?',
                'answer' => 'You can reset your password by clicking on "Forgot Password" at the login page and following the instructions.',
                'type' => 'user',
                'status' => 'active',
            ],
            [
                'question' => 'How can I manage users?',
                'answer' => 'Admins can manage users through the admin dashboard under the "Users" section.',
                'type' => 'admin',
                'status' => 'active',
            ],
            [
                'question' => 'How to become a host?',
                'answer' => 'To become a host, please apply through the host application page and wait for approval.',
                'type' => 'venue_holder',
                'status' => 'active',
            ],
            [
                'question' => 'What if I encounter a technical issue?',
                'answer' => 'Please contact support with a detailed description of the issue and we will assist you as soon as possible.',
                'type' => 'entertainer',
                'status' => 'active',
            ],
        ];

        foreach ($faqs as $faq) {
            Faq::create($faq);
        }
    }
}
