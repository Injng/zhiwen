<?php

namespace Database\Seeders;

use App\Models\Definition;
use App\Models\Entry;
use App\Models\Example;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Entry::factory()->create([
            'word' => '你',
            'pinyin' => 'nǐ',
        ]);

        Definition::factory()->create([
            'entry_id' => 1,
            'part' => '代',
            'definition' => '称对方（一个人）',
        ]);

        Example::factory()->create([
            'definition_id' => 1,
            'sentence' => '你好，世界。',
        ]);
    }
}
