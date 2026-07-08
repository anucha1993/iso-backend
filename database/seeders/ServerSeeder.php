<?php

namespace Database\Seeders;

use App\Models\Server;
use Illuminate\Database\Seeder;

class ServerSeeder extends Seeder
{
    public function run(): void
    {
        $servers = [
            'THEFIRST_SERVER02',
            'THEFIRST_SERVER04',
            'THEFIRST_SERVER05',
        ];

        foreach ($servers as $name) {
            Server::updateOrCreate(
                ['name' => $name],
                ['responsible' => 'TI_SUPPORT', 'is_active' => true],
            );
        }
    }
}
