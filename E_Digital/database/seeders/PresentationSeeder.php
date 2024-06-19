<?php

namespace Database\Seeders;

use Faker\Factory as Faker;
use App\Models\Slide;
use App\Models\SlideType;
use App\Models\Presentation;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PresentationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->faker = Faker::create();
        $user = \App\Models\User::first();

        Presentation::factory()
            ->for($user)
            ->create([
                'created_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
                'updated_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            ])->each(function ($presentation) {
                Slide::create(array_merge(
                    self::getTestSlideData('pick_answer'),
                    ['presentation_id' => $presentation->id]
                ));
                Slide::create(array_merge(
                    self::getTestSlideData('pick_image'),
                    ['presentation_id' => $presentation->id]
                ));
                Slide::create(array_merge(
                    self::getTestSlideData('short_answer'),
                    ['presentation_id' => $presentation->id]
                ));
                $presentation->update([
                    'name' => 'Что такое?',
                    'created_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
                    'updated_at' => $this->faker->dateTimeBetween($presentation->created_at, 'now'),
                ]);
            });

    }

    private static array $testData = [
        'pick_answer' => [
            'question' => 'Что такое осень? Это...',
            'name' => 'Что такое осень? Это...',
            'options' => [
                ['text' => 'Небо'],
                ['text' => 'Камни'],
                ['text' => 'Ветер'],
            ],
        ],
        'pick_image' => [
            'question' => 'Кто живёт на дне океана?',
            'name' => 'Кто живёт на дне океана?',
            'options' => [
                ['text' => 'Губка Боб'],
                ['text' => 'Бубка Гоб'],
                ['text' => 'Убка Об'],
            ],
        ],
        'short_answer' => [
            'question' => 'Какой город в мире имеет самое большое население?',
            'name' => 'Какой город в мире имеет самое большое население?',
            'options' => [
                'correct_answer' => 'Токио',
                'accepted_answers' => ['Шанхай', 'Пекин', 'Нью-Йорк'],
            ],
        ],
    ];

    private static function getTestSlideData($type_code)
    {
        $slide_data = [
            'name' => self::$testData[$type_code]['name'],
            'settings' => SlideType::getDefaultSettings($type_code),
            'design' => SlideType::getDefaultDesign($type_code),
            'slide_type_id' => SlideType::where('code', $type_code)->first()->id,
        ];
        $question = self::$testData[$type_code]['question'];
        $options = self::$testData[$type_code]['options'];
        $slide_data['settings']['question'] = $question;
        $slide_data['settings']['options'] = $options;

        return $slide_data;
    }
}
