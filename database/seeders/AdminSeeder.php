<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'kummer.marifelgrace@gmail.com'],
            [
                'first_name' => 'Marifel Grace',
                'last_name'  => 'Kummer',
                'password'   => Hash::make('admin1234'),
                'role'       => 'admin',
                'status'     => 'active',
                'is_adviser' => false,
                'course_id'  => null,
            ]
        );

        User::updateOrCreate(
            ['email' => 'santos.ricardo@srms.site'],
            [
                'first_name' => 'Ricardo',
                'last_name'  => 'Santos',
                'password'   => Hash::make('admin1234'),
                'role'       => 'admin',
                'status'     => 'active',
                'is_adviser' => false,
                'course_id'  => null,
            ]
        );

        User::updateOrCreate(
            ['email' => 'delacruz.maria@srms.site'],
            [
                'first_name' => 'Maria',
                'last_name'  => 'Dela Cruz',
                'password'   => Hash::make('admin1234'),
                'role'       => 'admin',
                'status'     => 'active',
                'is_adviser' => false,
                'course_id'  => null,
            ]
        );
    }
}
