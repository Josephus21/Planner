<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostComment;
use Illuminate\Http\Request;

class PostCommentController extends Controller
{
    public function store(Request $request, Post $post)
    {
        $data = $request->validate([
            'body' => 'required|string|max:2000',
        ]);

        $comment = PostComment::create([
            'post_id' => $post->id,
            'user_id' => auth()->id(),
            'body' => $data['body'],
        ]);

        $comment->load('user:id,name');

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'post_id' => $post->id,
                'comment' => [
                    'id' => $comment->id,
                    'user_name' => $comment->user->name,
                    'body' => $comment->body,
                    'created_human' => $comment->created_at->diffForHumans(),
                ],
                'comment_count' => PostComment::where('post_id', $post->id)->count(),
            ]);
        }

        return back();
    }
}