<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_saml_connections', function (Blueprint $table): void {
            $table->id();
            $table->string('organization_id')->unique();
            $table->string('idp_entity_id', 2048);
            $table->string('sso_url', 2048);
            $table->text('x509_certificate');
            $table->string('email_attribute')->default('email');
            $table->string('name_attribute')->default('name');
            $table->boolean('enabled')->default(false);
            $table->timestamps();
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
        });

        Schema::create('saml_authentication_requests', function (Blueprint $table): void {
            $table->char('state_hash', 64)->primary();
            $table->string('organization_id');
            $table->string('request_id', 255);
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();
            $table->index(['organization_id', 'expires_at']);
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saml_authentication_requests');
        Schema::dropIfExists('organization_saml_connections');
    }
};
