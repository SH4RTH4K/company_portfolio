<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PostCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PostController extends Controller
{
    public function index()
    {
        $posts = Post::with(['category', 'author'])->orderBy('created_at', 'desc')->get();

        return view('admin.posts.index', compact('posts'));
    }

    public function create()
    {
        $categories = PostCategory::where('is_active', true)->get();

        return view('admin.posts.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title_en' => 'required|string|max:255',
            'title_bn' => 'nullable|string|max:255',
            'excerpt_en' => 'nullable|string',
            'excerpt_bn' => 'nullable|string',
            'content_en' => 'nullable|string',
            'content_bn' => 'nullable|string',
            'featured_image' => 'nullable|string|max:255',
            'category_id' => 'nullable|exists:post_categories,id',
            'tags' => 'nullable|string|max:500',
            'status' => 'required|in:draft,published',
            'published_at' => 'nullable|date',
        ]);

        $validated['slug_en'] = Str::slug($validated['title_en']);
        $validated['slug_bn'] = Str::slug($validated['title_bn'] ?? '');
        $validated['author_id'] = auth()->id();

        if ($validated['status'] === 'published' && empty($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        $post = Post::create($validated);
        $post->syncSeoMeta($request->all());

        return redirect()->route('admin.posts.index')
            ->with('success', 'Post created successfully.');
    }

    public function show(Post $post)
    {
        return redirect()->route('admin.posts.index');
    }

    public function edit(Post $post)
    {
        $categories = PostCategory::where('is_active', true)->get();

        return view('admin.posts.edit', compact('post', 'categories'));
    }

    public function update(Request $request, Post $post)
    {
        $validated = $request->validate([
            'title_en' => 'required|string|max:255',
            'title_bn' => 'nullable|string|max:255',
            'excerpt_en' => 'nullable|string',
            'excerpt_bn' => 'nullable|string',
            'content_en' => 'nullable|string',
            'content_bn' => 'nullable|string',
            'featured_image' => 'nullable|string|max:255',
            'category_id' => 'nullable|exists:post_categories,id',
            'tags' => 'nullable|string|max:500',
            'status' => 'required|in:draft,published',
            'published_at' => 'nullable|date',
        ]);

        $validated['slug_en'] = Str::slug($validated['title_en']);
        $validated['slug_bn'] = Str::slug($validated['title_bn'] ?? '');

        if ($validated['status'] === 'published' && empty($validated['published_at']) && ! $post->published_at) {
            $validated['published_at'] = now();
        }

        $post->update($validated);
        $post->syncSeoMeta($request->all());

        return redirect()->route('admin.posts.index')
            ->with('success', 'Post updated successfully.');
    }

    public function destroy(Post $post)
    {
        $post->delete();

        return redirect()->route('admin.posts.index')
            ->with('success', 'Post deleted successfully.');
    }
}
