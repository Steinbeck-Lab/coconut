<div>
    <article class="mt-32 min-h-screen py-16">
        <div class="mx-auto max-w-3xl px-6 lg:px-8">
            <header class="mb-10">
                <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-gray-500">
                    @if ($post->published_at)
                        <time datetime="{{ $post->published_at->toDateString() }}">
                            {{ $post->published_at->format('F j, Y') }}
                        </time>
                    @endif
                    @if ($post->category)
                        <span class="rounded-full bg-gray-100 px-3 py-1 font-medium text-gray-600">
                            {{ $post->category->name }}
                        </span>
                    @endif
                </div>
                <h1 class="mt-4 text-4xl font-bold tracking-tight text-gray-900 sm:text-5xl">
                    {{ $post->title }}
                </h1>
                @if ($post->excerpt)
                    <p class="mt-6 text-xl text-gray-600">{{ $post->excerpt }}</p>
                @endif
                @if ($post->author)
                    <p class="mt-6 text-sm font-semibold text-gray-900">{{ $post->author->name }}</p>
                @endif
            </header>

            @if ($post->banner_url)
                <img src="{{ $post->banner_url }}" alt=""
                    class="mb-10 aspect-[16/9] w-full rounded-2xl bg-gray-100 object-cover">
            @endif

            <div class="prose prose-slate max-w-none">
                {!! $post->content !!}
            </div>
        </div>
    </article>
</div>
