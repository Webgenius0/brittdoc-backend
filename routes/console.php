<?php

use App\Console\Commands\AutoCompleteBookings;
use App\Console\Commands\UpdateContractorRanking;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;


// Artisan::command('inspire', function () {
//     $this->comment(Inspiring::quote());
// })->purpose('Display an inspiring quote')->hourly();
// Schedule::call( function () {
//     logger()->info('test it');
// })->everySecond();
// Schedule::command(AutoCompleteBookings::class)->everyFourHours();
// Schedule::command(UpdateContactorStatistics::class)->cron('0 0 */3 * *');


// routes/console.php


Schedule::command('app:booking-status-update')->everyMinute();