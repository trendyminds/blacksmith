<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Forge\Forge;

class ListSandboxes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sandbox:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Lists all sandboxes on Forge';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $forge = new Forge(config('app.forge.token'));
            collect($forge->sites(config('app.forge.server_id')))
                ->filter(fn ($site) => preg_match('/\w+\-\d\./', $site->name))
                ->each(fn ($site) => $this->info($site->name));

        } catch (\Exception $e) {
            $this->error('Could not list sandboxes');
            $this->error($e->getMessage());
        }
    }
}
