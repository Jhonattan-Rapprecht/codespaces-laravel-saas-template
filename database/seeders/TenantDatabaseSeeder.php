<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TenantDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('roles')->upsert([
            ['key' => 'tenant-admin', 'name' => 'Tenant administrator'],
            ['key' => 'member', 'name' => 'Member'],
        ], ['key'], ['name']);

        $email = tenant('initial_admin_email');
        $password = tenant('initial_admin_password');

        if (! $email || ! $password) {
            return;
        }

        $user = User::query()->firstOrCreate(
            ['email' => $email],
            ['name' => tenant('initial_admin_name') ?: 'Tenant Administrator', 'password' => Hash::make($password)],
        );

        $roleId = DB::table('roles')->where('key', 'tenant-admin')->value('id');
        DB::table('role_user')->insertOrIgnore(['role_id' => $roleId, 'user_id' => $user->id]);
    }
}
