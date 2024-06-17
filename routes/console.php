<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('triage:run')->everyMinute();
Schedule::command('backups:prune')->everyMinute();
