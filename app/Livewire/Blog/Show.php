<?php

namespace App\Livewire\Blog;

use Illuminate\Support\Str;
use Livewire\Component;
use Stephenjude\FilamentBlog\Models\Post;

class Show extends Component
{
    public Post $post;

    public function mount(string $slug): void
    {
        $this->post = Post::query()
            ->where('slug', $slug)
            ->published()
            ->where('published_at', '<=', now())
            ->with(['author', 'category'])
            ->firstOrFail();
    }

    public function render()
    {
        return view('livewire.blog.show')
            ->layout('layouts.guest', [
                'title' => $this->post->title,
                'description' => $this->post->excerpt
                    ?: Str::limit(strip_tags((string) $this->post->content), 160),
                'image' => $this->post->banner_url ?: null,
            ]);
    }
}
