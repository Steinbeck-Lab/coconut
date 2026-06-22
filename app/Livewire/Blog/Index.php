<?php

namespace App\Livewire\Blog;

use Livewire\Component;
use Livewire\WithPagination;
use Stephenjude\FilamentBlog\Models\Post;

class Index extends Component
{
    use WithPagination;

    public function render()
    {
        $posts = Post::query()
            ->published()
            ->where('published_at', '<=', now())
            ->with(['author', 'category'])
            ->latest('published_at')
            ->paginate(12);

        return view('livewire.blog.index', [
            'posts' => $posts,
        ])->layout('layouts.guest', [
            'title' => 'Blog',
            'description' => 'News and updates from the COCONUT team.',
        ]);
    }
}
