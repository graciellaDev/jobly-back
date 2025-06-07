<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

//        User::factory()->create([
//            'name' => 'Test User',
//            'email' => 'test@example.com',
//        ]);
        $defaultData = [
            [
                'dbName' => 'permissions',
                'seederClass' => PermissionsTableSeeder::class
            ],
            [
                'dbName' => 'task_types',
                'seederClass' => TastTypesTableSeeder::class
            ],
            [
                'dbName' => 'phrases',
                'seederClass' => PhrasesTableSeeder::class
            ],
        ];

        foreach ($defaultData as $data) {
            $isData = DB::table($data['dbName'])->exists();
            if (!$isData) {
                $this->call($data['seederClass']);
            }
        }
    }
}
