<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SubacquirerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $mode = env('SUBACQUIRER_MODE', 'mock');

        $subacquirers = [
            [
                'name' => 'SubadqA',
                'code' => 'subadq_a',
                'base_url' => $mode === 'mock'
                    ? 'mock'
                    : env('SUBACQUIRER_A_BASE_URL', 'https://0acdeaee-1729-4d55-80eb-d54a125e5e18.mock.pstmn.io'),
                'is_active' => true,
            ],
            [
                'name' => 'SubadqB',
                'code' => 'subadq_b',
                'base_url' => $mode === 'mock'
                    ? 'mock'
                    : env('SUBACQUIRER_B_BASE_URL', 'https://ef8513c8-fd99-4081-8963-573cd135e133.mock.pstmn.io'),
                'is_active' => true,
            ],
        ];

        foreach ($subacquirers as $subacquirer) {
            \App\Models\Subacquirer::firstOrCreate(
                ['code' => $subacquirer['code']],
                $subacquirer
            );
        }
    }
}
