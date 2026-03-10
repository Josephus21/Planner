<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FeedController extends Controller
{
    public function index()
    {
        $posts = Post::with([
                'user:id,name',
                'comments.user:id,name',
                'reactions'
            ])
            ->latest()
            ->paginate(10);

        return view('feed.index', compact('posts'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'body' => 'required|string|max:5000',
            'image' => 'nullable|image|max:4096',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('posts', 'public');
        }

        Post::create([
            'user_id' => auth()->id(),
            'body' => $data['body'],
            'image_path' => $imagePath,
        ]);

        return back()->with('success', 'Posted!');
    }

    public function destroy(Post $post)
{
    $user = auth()->user();

    // ✅ Authorization:
    // Owner can delete own post
    $isOwner = ((int)$post->user_id === (int)$user->id);

    // If you have permission helper, use it. Otherwise simplest:
    // Developer/Admin check (adjust to your RBAC):
    $canDeleteAny = false;

    // If your system uses employees.role_id -> roles.title:
    $myEmployeeId = $user->employee_id ? (int)$user->employee_id : null;
    if ($myEmployeeId) {
        $roleTitle = \Illuminate\Support\Facades\DB::table('employees as e')
            ->leftJoin('roles as r', 'r.id', '=', 'e.role_id')
            ->where('e.id', $myEmployeeId)
            ->value('r.title');

        $roleTitle = strtolower(trim((string)$roleTitle));
        $canDeleteAny = in_array($roleTitle, ['developer', 'admin'], true);
    }

    if (!$isOwner && !$canDeleteAny) {
        abort(403, 'You are not allowed to delete this post.');
    }

    // ✅ Delete image if any
    if ($post->image_path) {
        Storage::disk('public')->delete($post->image_path);
    }

    $post->delete();

    // For AJAX delete (optional)
    if (request()->expectsJson()) {
        return response()->json(['ok' => true, 'post_id' => $post->id]);
    }

    return back()->with('success', 'Post deleted.');
}
}