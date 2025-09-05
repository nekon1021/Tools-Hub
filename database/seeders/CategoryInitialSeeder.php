<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use Illuminate\Support\Arr;

class CategoryInitialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rows = [
            ['name'=>'SNS・Webサービス',        'slug'=>'sns-web-services',        'sort_order'=>10, 'is_active'=>true],
            ['name'=>'就職・転職・資格',        'slug'=>'career-jobs-cert',        'sort_order'=>20, 'is_active'=>true],
            ['name'=>'ライティング・ブログ運営', 'slug'=>'writing-blogging',        'sort_order'=>30, 'is_active'=>true],
            ['name'=>'教育・レポート・試験',     'slug'=>'education-reports-exams', 'sort_order'=>40, 'is_active'=>true],
            ['name'=>'プログラミング・技術',     'slug'=>'programming-tech',        'sort_order'=>50, 'is_active'=>true],
            ['name'=>'創作・小説・エッセイ',     'slug'=>'creative-novel-essay',    'sort_order'=>60, 'is_active'=>true],
            ['name'=>'時事・トレンド',           'slug'=>'news-trends',             'sort_order'=>70, 'is_active'=>true],
        ];

        foreach ($rows as $r) {
            $existing = Category::withTrashed()->where('slug', $r['slug'])->first();
            if ($existing && $existing->trashed()) {
                $existing->restore();
            }
            Category::updateOrCreate(
                ['slug'=>$r['slug']],
                Arr::only($r, ['name', 'sort_order', 'is_active'])
            );
        }
    }
}
