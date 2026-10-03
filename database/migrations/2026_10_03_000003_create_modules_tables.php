<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modules', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description');
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });

        Schema::create('organization_modules', function (Blueprint $table): void {
            $table->string('organization_id');
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreignId('module_id')->constrained('modules')->cascadeOnDelete();
            $table->boolean('enabled')->default(true);
            $table->timestamps();
            $table->primary(['organization_id', 'module_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_modules');
        Schema::dropIfExists('modules');
    }
};
