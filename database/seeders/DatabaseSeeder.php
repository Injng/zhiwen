<?php

namespace Database\Seeders;

use App\Models\Entry;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Entry::factory()->create([
            'word' => '你好',
            'pinyin' => 'nǐ hǎo',
        ]);
    }
}
