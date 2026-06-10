<?php

use Codemonster\Scheduler\Schedule;

if (!function_exists('schedule')) {
    function schedule(): Schedule
    {
        /** @var Schedule $schedule */
        $schedule = app(Schedule::class);

        return $schedule;
    }
}
