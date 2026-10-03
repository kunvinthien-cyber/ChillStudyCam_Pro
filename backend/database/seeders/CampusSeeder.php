<?php

namespace Database\Seeders;

use App\Models\Campus;
use Illuminate\Database\Seeder;

class CampusSeeder extends Seeder
{
    public function run(): void
    {
        $campuses = [
            ['code' => 'rupp', 'name' => 'Royal University of Phnom Penh', 'khmer_name' => 'RUPP (ភូមិន្ទភ្នំពេញ)', 'total_hours' => 5420.0, 'students_count' => 1240],
            ['code' => 'itc', 'name' => 'Institute of Technology of Cambodia', 'khmer_name' => 'ITC (តិចណូ)', 'total_hours' => 4890.0, 'students_count' => 980],
            ['code' => 'puc', 'name' => 'Paññāsāstra University of Cambodia', 'khmer_name' => 'PUC (បញ្ញាសាស្ត្រ)', 'total_hours' => 3890.0, 'students_count' => 750],
            ['code' => 'num', 'name' => 'National University of Management', 'khmer_name' => 'NUM (ជាតិគ្រប់គ្រង)', 'total_hours' => 2940.0, 'students_count' => 620],
        ];

        foreach ($campuses as $c) {
            Campus::updateOrCreate(['code' => $c['code']], $c);
        }
    }
}
