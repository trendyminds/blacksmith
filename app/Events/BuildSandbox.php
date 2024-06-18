<?php

namespace App\Events;

use App\Models\Sandbox;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Laravel\Forge\Exceptions\ValidationException;
use Laravel\Forge\Forge;
use Laravel\Forge\Resources\Site;

class BuildSandbox
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * A Forge instance to interact with the API
     */
    public Forge $forge;

    /**
     * The server ID to create the site on
     */
    public int $serverId;

    /**
     * Create a new event instance.
     */
    public function __construct(public Sandbox $sandbox)
    {
        $this->forge = new Forge(config('app.forge.token'));
        $this->serverId = config('app.forge.server_id');

        static::createSite();
        static::createDatabase();
        static::mountRepo();
        static::enableSSL();
    }

    /**
     * Create the site on Forge
     */
    protected function createSite()
    {
        try {
            $this->forge->createSite($this->serverId, [
                'domain' => $this->sandbox->domain,
                'project_type' => 'php',
                'php_version' => $this->sandbox->php_version,
                'directory' => $this->sandbox->doc_root,
                'aliases' => $this->sandbox->aliases,
            ]);
        } catch (ValidationException $e) {
            dump($e->errors());
            throw new ValidationException($e->errors());
        }
    }

    /**
     * Create the database on Forge
     */
    protected function createDatabase()
    {
        try {
            $this->forge->createDatabase($this->serverId, [
                'name' => $this->sandbox->database,
            ]);

            $site = static::_getSiteByDomain($this->sandbox->domain);

            // Check if a seed.db file exists in /home/forge/configs/{app_name} and if so seed the database
            $this->forge->executeSiteCommand($this->serverId, $site->id, [
                'command' => "[[ -f {$this->sandbox->configDirectory}/seed.db ]] && mysql {$this->sandbox->database} < {$this->sandbox->configDirectory}/seed.db",
            ]);
        } catch (ValidationException $e) {
            dump($e->errors());
            throw new ValidationException($e->errors());
        }
    }

    /**
     * Mounts the repository on the server if the repo and branch options are provided
     */
    protected function mountRepo()
    {
        // Exit early if the repo or branch are not provided
        if (! $this->sandbox->repo || ! $this->sandbox->branch) {
            return;
        }

        try {
            static::_getSiteByDomain($this->sandbox->domain)
                ->installGitRepository([
                    'provider' => 'github',
                    'repository' => $this->sandbox->repo,
                    'branch' => $this->sandbox->branch,
                    'composer' => false,
                    'database' => null,
                    'migrate' => false,
                ]);
        } catch (ValidationException $e) {
            dump($e->errors());
            throw new ValidationException($e->errors());
        }
    }

    /**
     * Provisions a Let's Encrypt SSL certificate for the site
     */
    protected function enableSSL()
    {
        // Exit early if the SSL option is not enabled
        if (! $this->sandbox->ssl) {
            return;
        }

        try {
            $site = static::_getSiteByDomain($this->sandbox->domain);

            $this->forge->obtainLetsEncryptCertificate(
                $this->serverId,
                $site->id,
                ['domains' => [$this->sandbox->domain, ...$this->sandbox->aliases]],
                false
            );
        } catch (ValidationException $e) {
            dump($e->errors());
            throw new ValidationException($e->errors());
        }
    }

    /**
     * Gets a site from Forge by its domain
     */
    private function _getSiteByDomain(string $domain): Site
    {
        $allSites = $this->forge->sites($this->serverId);

        return collect($allSites)
            ->filter(fn ($site) => $site->name === $domain)
            ->firstOrFail();
    }
}
