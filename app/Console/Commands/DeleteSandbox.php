<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Forge\Forge;
use Spatie\DbDumper\Compressors\GzipCompressor;
use Spatie\DbDumper\Databases\MySql;

class DeleteSandbox extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sandbox:delete
                            {app_name : Used to generate the domain and database name}
                            {pr_number : The number for the pull request used to build the app and database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes a sandbox from Forge';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $serverId = config('app.forge.server_id');
        $appName = $this->argument('app_name');
        $prNumber = $this->argument('pr_number');
        $domain = "{$appName}-{$prNumber}.".config('app.forge.review_app_domain');

        try {
            $forge = new Forge(config('app.forge.token'));

            $database = collect($forge->databases($serverId))
                ->filter(fn ($database) => $database->name === "{$appName}_{$prNumber}")
                ->first();

            $site = collect($forge->sites($serverId))
                ->filter(fn ($site) => $site->name === $domain)
                ->first();

            if ($database) {
                $this->info('🗑️ Backing up and deleting database');

                MySql::create()
                    ->setDbName($database->name)
                    ->setUserName(config('app.forge.mysql_user'))
                    ->setPassword(config('app.forge.mysql_password'))
                    ->useCompressor(new GzipCompressor())
                    ->dumpToFile(storage_path("db_backups/{$database->name}.sql.gz"));

                $database->delete();
            }

            if ($site) {
                $this->info('🗑️ Deleting site');
                $site->delete();
            }

            if (! $site && ! $database) {
                $this->info("🌐 Site and database for $domain do not exist");
                $this->info('Aborting deletion process.');

                return;
            }
        } catch (\Exception $e) {
            $this->error($e);
        }
    }
}
