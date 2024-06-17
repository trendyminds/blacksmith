<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Forge\Forge;

class CreateSandbox extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sandbox:create
                            {app_name : Used to generate the domain and database name}
                            {pr_number : The number for the pull request used to build the app and database}
                            {--php=php83 : The version of PHP to use for the app}
                            {--doc_root=/public : The document root for the site}
                            {--repo= : The organization/repo-name for mounting the app on Forge}
                            {--branch= : The branch to deploy}
                            {--post_deploy= : The post-deploy commands to run after deployment}
                            {--alias=* : Any other domains that should be used for the site}
                            {--disable_ssl : Disable SSL for the site}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a new sandbox on Forge';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $serverId = config('app.forge.server_id');
        $appName = $this->argument('app_name');
        $prNumber = $this->argument('pr_number');
        $domain = "{$appName}-{$prNumber}.".config('app.forge.review_app_domain');
        $aliases = collect($this->option('alias'))->filter()->values()->toArray();

        try {
            $forge = new Forge(config('app.forge.token'));

            // Check if the site already exists
            $allSites = $forge->sites($serverId);
            $site = collect($allSites)
                ->filter(fn ($site) => $site->name === $domain)
                ->first();

            if ($site) {
                $this->info("🌐 Skipping creation. Site already exists: $domain");

                return;
            }

            // Create the site
            $this->info('🌐 Creating site');
            $site = $forge->createSite($serverId, [
                'domain' => $domain,
                'project_type' => 'php',
                'php_version' => $this->option('php'),
                'directory' => $this->option('doc_root'),
                'aliases' => $aliases,
            ]);

            // Create a database
            $this->info('🗄️ Setting up database');
            $forge->createDatabase($serverId, ['name' => "{$appName}_{$prNumber}"]);

            // If a seed database exists, seed the database
            $this->info('🌱 Seeding the database (if seed.db file exists)');
            $forge->executeSiteCommand($serverId, $site->id, [
                'command' => "[[ -f /home/forge/configs/{$appName}/seed.db ]] && mysql {$appName}_{$prNumber} < /home/forge/configs/blk/seed.db",
            ]);

            // Mount the Git repository and branch
            if ($this->option('repo') && $this->option('branch')) {
                $this->info('🔄 Mounting Git repository');
                $site->installGitRepository([
                    'provider' => 'github',
                    'repository' => $this->option('repo'),
                    'branch' => $this->option('branch'),
                    'composer' => false,
                    'database' => null,
                    'migrate' => false,
                ])->enableQuickDeploy();
            }

            // Copy over the required files
            $this->info('🛠️ Configuring environment');
            $commands = collect([
                "cp -f /home/forge/configs/$appName/.env /home/forge/$domain",
                "sed -i 's/APP_NAME/{$appName}-{$prNumber}/g' /home/forge/$domain/.env",
                "sed -i 's/DB_NAME/{$appName}_{$prNumber}/g' /home/forge/$domain/.env",
                "cp -f /home/forge/configs/robots.txt /home/forge/{$domain}{$site->directory}",
            ])->join(' && ');

            $forge->executeSiteCommand($serverId, $site->id, ['command' => $commands]);

            // Setup a Let's Encrypt SSL
            if (! $this->option('disable_ssl')) {
                $this->info('🔒 Installing SSL');
                $forge->obtainLetsEncryptCertificate($serverId, $site->id, ['domains' => [$domain, ...$aliases]], false);
            }

            // Adding post-deploy commands
            $postDeployScript = "/home/forge/configs/{$appName}/post-deploy.sh";
            if (file_exists($postDeployScript)) {
                $this->info('📦 Adding post-deploy commands');
                // Prepend the default post-deploy script to custom scripts
                $script = $site->getDeploymentScript()."\n";

                // Make sure NVM is sourced before running the custom script
                $script .= "source ~/.nvm/nvm.sh\n";
                $script .= file_get_contents($postDeployScript);
                $site->updateDeploymentScript($script);
            }

            // Trigger the first deploy
            $this->info('🚀 Deploying the site. All future deploys will be done by Forge.');
            $site->deploySite();
        } catch (\Exception $e) {
            $this->error("❌ Could not create site: $domain");
            $this->error($e);
        }
    }
}
