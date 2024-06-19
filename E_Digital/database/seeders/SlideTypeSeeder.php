<?php

namespace Database\Seeders;

use App\Models\SlideType;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class SlideTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SlideType::factory()->create([
            'name' => 'Выбрать ответ',
            'code' => 'pick_answer'
        ]);

        SlideType::factory()->create([
            'name' => 'Выбрать изображение',
            'code' => 'pick_image',
        ]);

        SlideType::factory()->create([
            'name' => 'Короткий ответ',
            'code' => 'short_answer',
        ]);

        SlideType::factory()->create([
            'name' => 'Вращать колесо',
            'code' => 'spin_wheel',
            'available' => false
        ]);

        SlideType::factory()->create([
            'name' => 'Сопоставление',
            'code' => 'match_pairs',
            'available' => false
        ]);

        SlideType::factory()->create([
            'name' => 'Правильный порядок',
            'code' => 'correct_order',
            'available' => false
        ]);
    }
}
