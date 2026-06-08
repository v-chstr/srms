<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StudentSeeder extends Seeder
{
    public function run(): void
    {
        $courses = Course::pluck('id', 'code');

        $students = [
            // IT — 8 students
            ['email' => 'fortes.jherilyn@gmail.com',    'first_name' => 'Jherilyn',    'last_name' => 'Fortes',       'course' => 'IT'],
            ['email' => 'lopez.cyrenejoy@gmail.com',    'first_name' => 'Cyrene Joy',  'last_name' => 'Lopez',        'course' => 'IT'],
            ['email' => 'santos.markanthony@gmail.com', 'first_name' => 'Mark Anthony','last_name' => 'Santos',       'course' => 'IT'],
            ['email' => 'reyes.angelica@gmail.com',     'first_name' => 'Angelica',    'last_name' => 'Reyes',        'course' => 'IT'],
            ['email' => 'dela.cruz.juan@gmail.com',     'first_name' => 'Juan',        'last_name' => 'Dela Cruz',    'course' => 'IT'],
            ['email' => 'garcia.mikaela@gmail.com',     'first_name' => 'Mikaela',     'last_name' => 'Garcia',       'course' => 'IT'],
            ['email' => 'ramos.christian@gmail.com',    'first_name' => 'Christian',   'last_name' => 'Ramos',        'course' => 'IT'],
            ['email' => 'mendoza.sophia@gmail.com',     'first_name' => 'Sophia',      'last_name' => 'Mendoza',      'course' => 'IT'],

            // CpE — 8 students
            ['email' => 'bautista.jose@gmail.com',      'first_name' => 'Jose',        'last_name' => 'Bautista',     'course' => 'CpE'],
            ['email' => 'castillo.patricia@gmail.com',  'first_name' => 'Patricia',    'last_name' => 'Castillo',     'course' => 'CpE'],
            ['email' => 'torres.ryan@gmail.com',        'first_name' => 'Ryan',        'last_name' => 'Torres',       'course' => 'CpE'],
            ['email' => 'flores.grace@gmail.com',       'first_name' => 'Grace',       'last_name' => 'Flores',       'course' => 'CpE'],
            ['email' => 'navarro.kenneth@gmail.com',    'first_name' => 'Kenneth',     'last_name' => 'Navarro',      'course' => 'CpE'],
            ['email' => 'aquino.mary.ann@gmail.com',    'first_name' => 'Mary Ann',    'last_name' => 'Aquino',       'course' => 'CpE'],
            ['email' => 'villanueva.carlo@gmail.com',   'first_name' => 'Carlo',       'last_name' => 'Villanueva',   'course' => 'CpE'],
            ['email' => 'perez.janine@gmail.com',       'first_name' => 'Janine',      'last_name' => 'Perez',        'course' => 'CpE'],

            // CE — 8 students
            ['email' => 'hernandez.roberto@gmail.com',  'first_name' => 'Roberto',     'last_name' => 'Hernandez',    'course' => 'CE'],
            ['email' => 'lim.claire@gmail.com',         'first_name' => 'Claire',      'last_name' => 'Lim',          'course' => 'CE'],
            ['email' => 'tan.joseph@gmail.com',         'first_name' => 'Joseph',      'last_name' => 'Tan',          'course' => 'CE'],
            ['email' => 'gonzales.anna@gmail.com',      'first_name' => 'Anna',        'last_name' => 'Gonzales',     'course' => 'CE'],
            ['email' => 'miranda.aldrin@gmail.com',     'first_name' => 'Aldrin',      'last_name' => 'Miranda',      'course' => 'CE'],
            ['email' => 'evangelista.rica@gmail.com',   'first_name' => 'Rica',        'last_name' => 'Evangelista',  'course' => 'CE'],
            ['email' => 'aguilar.jerome@gmail.com',     'first_name' => 'Jerome',      'last_name' => 'Aguilar',      'course' => 'CE'],
            ['email' => 'pascual.diana@gmail.com',      'first_name' => 'Diana',       'last_name' => 'Pascual',      'course' => 'CE'],

            // ENSE — 8 students
            ['email' => 'vargas.michael@gmail.com',     'first_name' => 'Michael',     'last_name' => 'Vargas',       'course' => 'ENSE'],
            ['email' => 'salazar.kristine@gmail.com',   'first_name' => 'Kristine',    'last_name' => 'Salazar',      'course' => 'ENSE'],
            ['email' => 'dela.rosa.eric@gmail.com',     'first_name' => 'Eric',        'last_name' => 'Dela Rosa',    'course' => 'ENSE'],
            ['email' => 'santiago.carla@gmail.com',     'first_name' => 'Carla',       'last_name' => 'Santiago',     'course' => 'ENSE'],
            ['email' => 'roxas.darwin@gmail.com',       'first_name' => 'Darwin',      'last_name' => 'Roxas',        'course' => 'ENSE'],
            ['email' => 'macaraeg.isabel@gmail.com',    'first_name' => 'Isabel',      'last_name' => 'Macaraeg',     'course' => 'ENSE'],
            ['email' => 'tolentino.don@gmail.com',      'first_name' => 'Don',         'last_name' => 'Tolentino',    'course' => 'ENSE'],
            ['email' => 'corpuz.liezel@gmail.com',      'first_name' => 'Liezel',      'last_name' => 'Corpuz',       'course' => 'ENSE'],

            // BLIS — 8 students
            ['email' => 'buenaventura.rex@gmail.com',   'first_name' => 'Rex',         'last_name' => 'Buenaventura', 'course' => 'BLIS'],
            ['email' => 'ocampo.mary.joy@gmail.com',    'first_name' => 'Mary Joy',    'last_name' => 'Ocampo',       'course' => 'BLIS'],
            ['email' => 'austria.joel@gmail.com',       'first_name' => 'Joel',        'last_name' => 'Austria',      'course' => 'BLIS'],
            ['email' => 'domingo.shiela@gmail.com',     'first_name' => 'Shiela',      'last_name' => 'Domingo',      'course' => 'BLIS'],
            ['email' => 'magno.lance@gmail.com',        'first_name' => 'Lance',       'last_name' => 'Magno',        'course' => 'BLIS'],
            ['email' => 'alcantara.irish@gmail.com',    'first_name' => 'Irish',       'last_name' => 'Alcantara',    'course' => 'BLIS'],
            ['email' => 'valenzuela.jomar@gmail.com',   'first_name' => 'Jomar',       'last_name' => 'Valenzuela',   'course' => 'BLIS'],
            ['email' => 'espiritu.nina@gmail.com',      'first_name' => 'Nina',        'last_name' => 'Espiritu',     'course' => 'BLIS'],
        ];

        foreach ($students as $student) {
            $courseId = $courses[$student['course']] ?? null;

            User::updateOrCreate(
                ['email' => $student['email']],
                [
                    'first_name' => $student['first_name'],
                    'last_name'  => $student['last_name'],
                    'password'   => Hash::make('student1234'),
                    'role'       => 'student',
                    'status'     => 'active',
                    'is_adviser' => false,
                    'course_id'  => $courseId,
                ]
            );
        }
    }
}
