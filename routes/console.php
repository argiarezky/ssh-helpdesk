<?php

use Illuminate\Foundation\DevCommands;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:send-pending-ticket-reminders')
    ->everyMinute()
    ->withoutOverlapping(5);

Schedule::command('app:send-due-ticket-reminders')
    ->everyMinute()
    ->withoutOverlapping(5);

Schedule::command('app:backup-database')
    ->dailyAt('02:00')
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping(120);

DevCommands::artisan('schedule:work', 'scheduler');
