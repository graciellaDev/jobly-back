<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('permissions')->insert([
            [
                'name' => 'Просматривать все вакансии',
            ],
            [
                'name' => 'Управлять вакансиями',
            ],
            [
                'name' => 'Удалять вакансии',
            ],
            [
                'name' => 'Назначать ответственных на вакансии',
            ],
            [
                'name' => 'Приглашать и назначать заказчиков',
            ],
            [
                'name' => 'Удалять кандидатов',
            ],
            [
                'name' => 'Управлять общими шаблонами писем',
            ],
            [
                'name' => 'Управлять тегами',
            ],
            [
                'name' => 'Получать заявки на вакансии',
            ],
        ]);
    }
}
