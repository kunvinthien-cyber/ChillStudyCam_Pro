<?php

namespace Database\Seeders;

use App\Models\Document;
use Illuminate\Database\Seeder;

class DocumentSeeder extends Seeder
{
    public function run(): void
    {
        $docs = [
            // 🎓 ឯកសារបាក់ឌុប (Grade 12)
            [
                'title' => 'វិញ្ញាសាគណិតវិទ្យាប្រឡងបាក់ឌុប + អត្រាកំណែផ្លូវការ',
                'description' => 'បណ្តុំលំហាត់លីមីត អាំងតេក្រាល ចំនួនកុំផ្លិច និងធរណីមាត្រក្នុងលំហ ត្រៀមប្រឡងយកនិទ្ទេស A។',
                'grade_level' => 'grade_12',
                'subject' => 'គណិតវិទ្យា',
                'file_url' => 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf', // Link PDF គំរូ
                'file_type' => 'pdf',
                'pts_cost' => 0,
                'downloads_count' => 342,
            ],
            [
                'title' => 'សន្លឹកសង្ខេបរូបមន្តរូបវិទ្យាថ្នាក់ទី ១២ (Cheat Sheet)',
                'description' => 'សង្ខេបរូបមន្តសង្ខេបខ្លីៗងាយចាំ គ្រប់ជំពូកទាំងអស់សម្រាប់ទន្ទេញមុនថ្ងៃប្រឡង។',
                'grade_level' => 'grade_12',
                'subject' => 'រូបវិទ្យា',
                'file_url' => 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
                'file_type' => 'pdf',
                'pts_cost' => 0,
                'downloads_count' => 512,
            ],
            // 🗣️ ឯកសារ IELTS & ភាសាបរទេស
            [
                'title' => 'IELTS Academic Writing Task 2 Templates (Band 7.5+)',
                'description' => 'គំរូរចនាសម្ព័ន្ធកថាខណ្ឌ ប្រយោគតភ្ជាប់គន្លឹះ និងកម្រងពាក្យកម្រិតខ្ពស់សម្រាប់តែងសេចក្តី IELTS។',
                'grade_level' => 'language',
                'subject' => 'IELTS',
                'file_url' => 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
                'file_type' => 'pdf',
                'pts_cost' => 50, // ប្រើ 50 PTS ដោះសោ
                'downloads_count' => 189,
            ],
            // 🏛️ ឯកសារនិស្សិតសាកលវិទ្យាល័យ (Programming & IT)
            [
                'title' => 'Fullstack Web Development Roadmap & Git Cheatsheet',
                'description' => 'មគ្គុទ្ទេសក៍សិក្សា Web Development (Vue, Laravel, SQL) និងបញ្ជីពាក្យបញ្ជា Git សំខាន់ៗ។',
                'grade_level' => 'university',
                'subject' => 'Programming',
                'file_url' => 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
                'file_type' => 'pdf',
                'pts_cost' => 0,
                'downloads_count' => 278,
            ],
        ];

        foreach ($docs as $d) {
            Document::create($d);
        }
    }
}
