<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PhrasesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('phrases')->insert([
            [
                'name' => 'Дизайн',
            ],
            [
                'name' => 'Аналитика',
            ],
            [
                'name' => 'Разработка',
            ],
            [
                'name' => 'Тестирование',
            ],
            [
                'name' => 'Продажи'
            ],
            [
                'name' => 'Маркетинг'
            ],
        ]);
    }
}
