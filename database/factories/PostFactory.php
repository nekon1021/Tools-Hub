<?php

namespace Database\Factories;

use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        $title = $this->faker->unique()->sentence(6);
        return [
            'user_id' => 1, // 管理者IDに合わせて調整
            'title'   => $title,
            'slug'    => Str::slug($title) . '-' . $this->faker->numberBetween(10, 9999),
            'body'    => $this->faker->paragraphs(8, true),
            'meta_title' => null,
            'meta_description' => null,
            'og_image_path' => null,
            'is_published' => $this->faker->boolean(75),
            'published_at' => now()->subDays(rand(0, 30)),
        ];
    }
}
