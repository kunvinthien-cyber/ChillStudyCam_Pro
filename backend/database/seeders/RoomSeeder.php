<?php

namespace Database\Seeders;

use App\Models\Room;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        // សម្អាតបន្ទប់ចាស់ៗសិន
        $rooms = [
            // 🎓 ១. បន្ទប់ថ្នាក់ទី ១២ (បាក់ឌុប)
            [
                'title' => 'បន្ទប់បាក់ឌុប A+ (គណិត & រូបវិទ្យា)',
                'subtitle_khmer' => 'ផ្តោតលើលំហាត់អាំងតេក្រាល លីមីត និងរូបវិទ្យាអគ្គិសនី',
                'description' => 'បន្ទប់ស្ងប់ស្ងាត់សម្រាប់សិស្សថ្នាក់ទី ១២ ដែលត្រៀមប្រឡងបាក់ឌុបយកនិទ្ទេស A។ ហាមនិយាយរឿងឥតប្រយោជន៍។',
                'category_tag' => '🎓 បាក់ឌុប BacII',
                'badge_tag' => 'ថ្នាក់ទី ១២',
                'ambient_title' => 'Deep Focus & Brain Waves',
                'thumbnail' => 'https://images.unsplash.com/photo-1434030216411-0b793f4b4173?q=80&w=1000&auto=format&fit=crop',
                'active_students' => 45,
                'avatars' => ['B12', 'SO', 'CH'],
                'more_count' => 42,
                'is_sanctuary' => true,
                'grade_level' => 'grade_12',
                'subject_focus' => 'គណិត & រូបវិទ្យា',
            ],
            // 🏛️ ២. បន្ទប់និស្សិតសាកលវិទ្យាល័យ (IT & Tech)
            [
                'title' => 'Toul Kork Tech Hub (RUPP & ITC)',
                'subtitle_khmer' => 'បន្ទប់សរសេរកូដ Coding, Assignments & Final Projects',
                'description' => 'សម្រាប់និស្សិតឆ្នាំទី ១ ដល់ឆ្នាំទី ៤ ផ្នែក IT, Computer Science, និង Engineering មកអង្គុយសរសេរកូដ និងធ្វើ Project ជាមួយគ្នា។',
                'category_tag' => '🏛️ សាកលវិទ្យាល័យ',
                'badge_tag' => 'IT & Engineering',
                'ambient_title' => 'Lofi Beats & Espresso Steam',
                'thumbnail' => 'https://images.unsplash.com/photo-1501339847302-ac426a4a7cbb?q=80&w=1000&auto=format&fit=crop',
                'active_students' => 28,
                'avatars' => ['DEV', 'IT', 'JS'],
                'more_count' => 25,
                'is_sanctuary' => false,
                'grade_level' => 'university',
                'subject_focus' => 'Programming',
            ],
            // 🗣️ ៣. បន្ទប់ត្រៀម IELTS & ភាសាអង់គ្លេស
            [
                'title' => 'IELTS 7.0+ Sanctuary',
                'subtitle_khmer' => 'Reading, Writing Task 2 & Speaking Practice Room',
                'description' => 'បន្ទប់អនុវត្តភាសាអង់គ្លេស សម្រាប់អ្នកត្រៀមប្រឡងយកអាហារូបករណ៍ទៅសិក្សានៅបរទេស។',
                'category_tag' => '🗣️ ភាសាបរទេស',
                'badge_tag' => 'IELTS & Languages',
                'ambient_title' => 'Soft Rain & Library Silence',
                'thumbnail' => 'https://images.unsplash.com/photo-1518495973542-4542c06a5843?q=80&w=1000&auto=format&fit=crop',
                'active_students' => 19,
                'avatars' => ['ENG', 'IL', 'US'],
                'more_count' => 16,
                'is_sanctuary' => false,
                'grade_level' => 'language',
                'subject_focus' => 'IELTS / អង់គ្លេស',
            ],
            // 📚 ៤. បន្ទប់វិទ្យាល័យទូទៅ (ថ្នាក់ទី ១០ និង ១១)
            [
                'title' => 'បន្ទប់វិទ្យាល័យទូទៅ (ថ្នាក់ទី ១០-១១)',
                'subtitle_khmer' => 'កន្លែងធ្វើកិច្ចការផ្ទះប្រចាំថ្ងៃ និងពិភាក្សាលំហាត់ជាក្រុម',
                'description' => 'សម្រាប់សិស្សវិទ្យាល័យថ្នាក់ទី ១០ និង ១១ អង្គុយរៀន និងស្រាវជ្រាវមេរៀនសាលាដោយសេរី។',
                'category_tag' => '📚 វិទ្យាល័យទូទៅ',
                'badge_tag' => 'ថ្នាក់ទី ១០-១១',
                'ambient_title' => 'Siem Reap Forest Breeze',
                'thumbnail' => 'https://images.unsplash.com/photo-1569154941061-e231b4725ef1?q=80&w=1000&auto=format&fit=crop',
                'active_students' => 15,
                'avatars' => ['G10', 'G11', 'KH'],
                'more_count' => 12,
                'is_sanctuary' => false,
                'grade_level' => 'high_school',
                'subject_focus' => 'ទូទៅ',
            ]
        ];

        foreach ($rooms as $r) {
            Room::updateOrCreate(['title' => $r['title']], $r);
        }
    }
}
