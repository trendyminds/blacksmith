<?php

namespace App\Events;

use App\Models\Sandbox;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Laravel\Forge\Forge;
use Laravel\Forge\Resources\Site;
use Spatie\DbDumper\Compressors\GzipCompressor;
use Spatie\DbDumper\Databases\MySql;

class CleanupSandbox
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

        static::deleteSite();
        static::deleteDatabase();
    }

    /**
     * Deletes the given site from Forge if it exists
     */
    protected function deleteSite()
    {
        $site = collect($this->forge->sites($this->serverId))
            ->filter(fn ($site) => $site->name === $this->sandbox->domain)
            ->first();

        if (! $site) {
            return;
        }

        $site->delete();
    }

    /**
     * Deletes the given site from Forge if it exists
     */
    protected function deleteDatabase()
    {
        $database = collect($this->forge->databases($this->serverId))
            ->filter(fn ($database) => $database->name === $this->sandbox->database)
            ->first();

        if (! $database) {
            return;
        }

        // Try to backup the database before deleting it
        // Check if the Forge database credentials are set beforehand
        if (config('app.forge.mysql_user') && config('app.forge.mysql_password')) {
            MySql::create()
                ->setDbName($this->sandbox->database)
                ->setUserName(config('app.forge.mysql_user'))
                ->setPassword(config('app.forge.mysql_password'))
                ->useCompressor(new GzipCompressor())
                ->dumpToFile(storage_path("db_backups/{$this->sandbox->database}.sql.gz"));
        }

        $database->delete();
    }

    /**
     * Gets a site from Forge by its domain
     */
    private function _getSiteByDomain(string $domain): ?Site
    {
        $allSites = $this->forge->sites($this->serverId);

        return collect($allSites)
            ->filter(fn ($site) => $site->name === $domain)
            ->first();
    }
}
