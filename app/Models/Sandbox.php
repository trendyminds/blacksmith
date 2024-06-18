<?php

namespace App\Models;

use App\Events\BuildSandbox;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sandbox extends Model
{
    use HasFactory, Prunable, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'aliases' => 'array',
        'ssl' => 'boolean',
    ];

    /**
     * The event map for the model.
     *
     * @var array<string, string>
     */
    protected $dispatchesEvents = [
        'created' => BuildSandbox::class,
    ];

    /**
     * The domain used for a given sandbox
     */
    protected function domain(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => (
                $attributes['app_name'].'-'.$attributes['pr_number'].'.'.config('app.forge.review_app_domain')
            ),
        );
    }

    /**
     * The database name used for a given sandbox
     */
    protected function database(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => (
                $attributes['app_name'].'_'.$attributes['pr_number']
            ),
        );
    }

    /**
     * The path to the config directory for this site
     */
    protected function configDirectory(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => '/home/forge/configs/'.$attributes['app_name'],
        );
    }

    /**
     * Prunes any sandbox deleted more than 24 hours ago
     */
    public function prunable(): Builder
    {
        return static::where('deleted_at', '<=', now()->hours(24));
    }
}
