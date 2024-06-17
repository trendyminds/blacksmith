<?php

namespace App\Console\Commands;

use App\Models\Triage;
use Illuminate\Console\Command;

class RunTriage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'triage:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Processes sandboxes that have been triaged for deletion';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Triage::where('created_at', '<=', now()->subDay())
            ->each(function ($triage) {
                [$appName, $prNumber] = explode('-', $triage->name);
                $this->call('sandbox:delete', [
                    'app_name' => $appName,
                    'pr_number' => $prNumber,
                ]);

                // Delete the triage record after the sandbox has been deleted
                $triage->delete();
            });
    }
}
