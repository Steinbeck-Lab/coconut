<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Stephenjude\FilamentBlog\Models\Author;
use Stephenjude\FilamentBlog\Models\Category;
use Stephenjude\FilamentBlog\Models\Post;

class BlogSeeder extends Seeder
{
    public function run(): void
    {
        $author = Author::query()->firstOrCreate(
            ['email' => 'blog@coconut.naturalproducts.net'],
            [
                'name' => 'COCONUT Team',
                'bio' => 'Updates from the COCONUT natural products database project.',
            ],
        );

        $category = Category::query()->firstOrCreate(
            ['slug' => 'news'],
            [
                'name' => 'News',
                'description' => 'Project news and announcements.',
                'is_visible' => true,
            ],
        );

        Post::query()->firstOrCreate(
            ['slug' => 'welcome-to-the-coconut-blog'],
            [
                'blog_author_id' => $author->id,
                'blog_category_id' => $category->id,
                'title' => 'Welcome to the COCONUT Blog',
                'excerpt' => 'Introducing our blog for project news, releases, and community updates.',
                'content' => '<p>Welcome to the COCONUT blog. Here we will share updates about the database, new features, and community highlights.</p><p>Posts are managed from the Coconut Dashboard by the team.</p>',
                'published_at' => now()->toDateString(),
            ],
        );
    }
}
