<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sandboxes', function (Blueprint $table) {
            $table->id();
            $table->string('app_name');
            $table->integer('pr_number');
            $table->string('php_version')->default('php83');
            $table->string('doc_root')->default('/public');
            $table->string('repo')->nullable();
            $table->string('branch')->nullable();
            $table->json('aliases')->default('[]');
            $table->boolean('ssl')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sandboxes');
    }
};
