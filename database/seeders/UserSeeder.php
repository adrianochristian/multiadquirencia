<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $subadqA = \App\Models\Subacquirer::where('code', 'subadq_a')->first();
        $subadqB = \App\Models\Subacquirer::where('code', 'subadq_b')->first();

        $users = [
            [
                'name' => 'Usuário A',
                'email' => 'usuario_a@example.com',
                'password' => bcrypt('password'),
                'subacquirer_id' => $subadqA->id,
            ],
            [
                'name' => 'Usuário B',
                'email' => 'usuario_b@example.com',
                'password' => bcrypt('password'),
                'subacquirer_id' => $subadqA->id,
            ],
            [
                'name' => 'Usuário C',
                'email' => 'usuario_c@example.com',
                'password' => bcrypt('password'),
                'subacquirer_id' => $subadqB->id,
            ],
        ];

        foreach ($users as $user) {
            \App\Models\User::firstOrCreate(
                ['email' => $user['email']],
                $user
            );
        }
    }
}
