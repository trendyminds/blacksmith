<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PruneBackups extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backups:prune';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes database backups that are older than 14 days';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        collect(Storage::disk('db_backups')->files())
            ->filter(fn ($file) => Str::endsWith($file, '.sql.gz'))
            ->filter(fn ($file) => now()->subDays(14)->timestamp > Storage::disk('db_backups')->lastModified($file))
            ->each(fn ($file) => Storage::disk('db_backups')->delete($file));
    }
}
