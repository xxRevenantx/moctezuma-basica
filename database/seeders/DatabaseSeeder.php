<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use PhpParser\Node\Scalar\MagicConst\Dir;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'moctezuma',
            'email' => 'moctezuma@basica.com',
            'password' => bcrypt('12345678'),
        ]);
        $this->call([
            // WorldTableSeeder::class
            EscuelaSeeder::class,
            DirectorSeeder::class,
        ]);
    }
}
