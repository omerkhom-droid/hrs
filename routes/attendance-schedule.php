<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('attendance:auto-check-out')
    ->everyFiveMinutes()
    ->withoutOverlapping(10);
