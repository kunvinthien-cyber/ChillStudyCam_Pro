<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // បង្កើត Default User
        User::firstOrCreate(
            ['email' => 'student@chillstudy.kh'],
            [
                'name' => 'Chamroeun',
                'password' => bcrypt('password123'),
                'coins' => 250,
                'streak_days' => 7,
                'rank_title' => 'Hanuman IV',
                'studied_hours' => 2.5,
                'target_hours' => 4.0,
            ]
        );

        // បញ្ចូលទំនិញពិតៗ
        $products = [
            [
                'name' => 'Iced Green Tea Latte (តែបៃតង)',
                'category' => 'ភេសជ្ជៈ',
                'price' => 1.50,
                'pts_price' => 150,
                'pts_discount' => 0.50,
                'image' => 'https://images.unsplash.com/photo-1536256263959-770b48d82b0a?q=80&w=400&auto=format&fit=crop',
                'partner_shop' => 'Tube Cafe (សាខា RUPP)'
            ],
            [
                'name' => 'Cold Brew Focus Coffee (កាហ្វេ)',
                'category' => 'ភេសជ្ជៈ',
                'price' => 2.00,
                'pts_price' => 200,
                'pts_discount' => 0.75,
                'image' => 'https://images.unsplash.com/photo-1517701550927-30cf4ba1dba5?q=80&w=400&auto=format&fit=crop',
                'partner_shop' => 'Brown Coffee (សាខា IFL)'
            ],
            [
                'name' => 'Matcha Croissant (នំក្រូសង់)',
                'category' => 'អាហារសម្រន់',
                'price' => 1.80,
                'pts_price' => 180,
                'pts_discount' => 0.50,
                'image' => 'https://images.unsplash.com/photo-1555507036-ab1f4038808a?q=80&w=400&auto=format&fit=crop',
                'partner_shop' => 'Bakery Corner (ក្បែរ ITC)'
            ]
        ];

        foreach ($products as $p) {
            Product::create($p);
        }
    }
}
