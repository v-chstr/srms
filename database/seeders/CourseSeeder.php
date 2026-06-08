<?php

namespace Database\Seeders;

use App\Models\Course;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        Course::updateOrCreate(['code' => 'IT'],   ['name' => 'Information Technology']);
        Course::updateOrCreate(['code' => 'CpE'],  ['name' => 'Computer Engineering']);
        Course::updateOrCreate(['code' => 'CE'],   ['name' => 'Civil Engineering']);
        Course::updateOrCreate(['code' => 'ENSE'], ['name' => 'Environmental and Sanitary Engineering']);
        Course::updateOrCreate(['code' => 'BLIS'],['name' => 'Bachelor of Library and Information Science']);
    }
}
