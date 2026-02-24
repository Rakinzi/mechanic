<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('stages:mark-overdue')->everyFiveMinutes();
