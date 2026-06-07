<div>
    <div class="mt-32 min-h-screen py-16">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <h1 class="text-4xl font-bold tracking-tight text-gray-900 sm:text-5xl">Blog</h1>
                <p class="mt-4 text-lg text-gray-600">
                    News and updates from the COCONUT team.
                </p>
            </div>

            @if ($posts->isEmpty())
                <p class="mx-auto mt-16 max-w-xl text-center text-gray-500">No posts published yet.</p>
            @else
                <div class="mx-auto mt-16 grid max-w-2xl grid-cols-1 gap-x-8 gap-y-20 lg:mx-0 lg:max-w-none lg:grid-cols-3">
                    @foreach ($posts as $post)
                        <article class="flex flex-col items-start">
                            @if ($post->banner_url)
                                <a href="{{ route('blog.show', $post->slug) }}" class="relative w-full">
                                    <img src="{{ $post->banner_url }}" alt=""
                                        class="aspect-[16/9] w-full rounded-2xl bg-gray-100 object-cover sm:aspect-[2/1] lg:aspect-[3/2]">
                                </a>
                            @endif
                            <div class="max-w-xl">
                                <div class="mt-8 flex items-center gap-x-4 text-xs">
                                    @if ($post->published_at)
                                        <time datetime="{{ $post->published_at->toDateString() }}" class="text-gray-500">
                                            {{ $post->published_at->format('M j, Y') }}
                                        </time>
                                    @endif
                                    @if ($post->category)
                                        <span class="relative z-10 rounded-full bg-gray-50 px-3 py-1.5 font-medium text-gray-600">
                                            {{ $post->category->name }}
                                        </span>
                                    @endif
                                </div>
                                <div class="group relative">
                                    <h2 class="mt-3 text-lg font-semibold leading-6 text-gray-900 group-hover:text-gray-600">
                                        <a href="{{ route('blog.show', $post->slug) }}">
                                            <span class="absolute inset-0"></span>
                                            {{ $post->title }}
                                        </a>
                                    </h2>
                                    @if ($post->excerpt)
                                        <p class="mt-5 line-clamp-3 text-sm leading-6 text-gray-600">{{ $post->excerpt }}</p>
                                    @endif
                                </div>
                                @if ($post->author)
                                    <div class="relative mt-8 flex items-center gap-x-4">
                                        <div class="text-sm leading-6">
                                            <p class="font-semibold text-gray-900">{{ $post->author->name }}</p>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="mt-16">
                    {{ $posts->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
