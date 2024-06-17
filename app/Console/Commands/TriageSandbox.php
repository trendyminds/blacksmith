<?php

namespace App\Console\Commands;

use App\Models\Triage;
use Illuminate\Console\Command;

class TriageSandbox extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sandbox:triage
                            {app_name : Used to generate the domain and database name}
                            {pr_number : The number for the pull request used to build the app and database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Triages a sandbox to be deleted after 24 hours';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Triage::create([
            'name' => $this->argument('app_name').'-'.$this->argument('pr_number'),
        ]);
    }
}
