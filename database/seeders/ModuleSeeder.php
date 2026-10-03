<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            [
                'slug' => 'team',
                'name' => 'Team',
                'description' => 'View tenant users and their roles.',
                'enabled' => true,
            ],
            [
                'slug' => 'settings',
                'name' => 'Settings',
                'description' => 'View organization and sign-in configuration.',
                'enabled' => true,
            ],
        ] as $module) {
            Module::query()->updateOrCreate(['slug' => $module['slug']], $module);
        }
    }
}
