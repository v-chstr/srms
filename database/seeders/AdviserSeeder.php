<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdviserSeeder extends Seeder
{
    public function run(): void
    {
        $it   = Course::where('code', 'IT')->first();
        $cpe  = Course::where('code', 'CpE')->first();
        $blis = Course::where('code', 'BLIS')->first();

        $advisers = [
            [
                'email'      => 'pugeda.rucelj@gmail.com',
                'first_name' => 'Rucelj',
                'last_name'  => 'Pugeda',
                'password'   => 'teacher1234',
                'course_id'  => $it?->id,
            ],
            [
                'email'      => 'babaran.carlosjr@gmail.com',
                'first_name' => 'Carlos',
                'last_name'  => 'Babaran Jr.',
                'password'   => 'teacher1234',
                'course_id'  => $cpe?->id,
            ],
        ];

        foreach ($advisers as $adviser) {
            User::updateOrCreate(
                ['email' => $adviser['email']],
                [
                    'first_name' => $adviser['first_name'],
                    'last_name'  => $adviser['last_name'],
                    'password'   => Hash::make($adviser['password']),
                    'role'       => 'adviser',
                    'status'     => 'active',
                    'is_adviser' => false,
                    'course_id'  => $adviser['course_id'],
                ]
            );
        }
    }
}
