<?php

namespace Database\Seeders;

use App\Models\PlatformSetting;
use Illuminate\Database\Seeder;

class PlatformSettingSeeder extends Seeder
{
    public function run(): void
    {
        // Usa firstOrCreate para garantir que apenas uma linha seja criada
        PlatformSetting::firstOrCreate(
            ['id' => 1],
            [
                'platform_name' => 'AvaMovie Platform',
                'contact_info' => json_encode([
                    'email' => 'contato@avamovie.com.br',
                    'whatsapp' => '+55 (41) 9185-8466',
                ]),
                'social_links' => json_encode([
                    'instagram' => 'https://instagram.com/iomargorges',
                    'landing_page' => 'https://grgsdev.com.br',
                ]),

            ]
        );
    }
}
