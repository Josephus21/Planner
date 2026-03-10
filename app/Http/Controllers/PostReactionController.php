<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostReaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PostReactionController extends Controller
{
    public function toggle(Request $request, Post $post)
    {
        $data = $request->validate([
            'type' => 'required|in:like,love,haha,wow,sad,angry',
        ]);

        $userId = auth()->id();

        $existing = PostReaction::where('post_id', $post->id)
            ->where('user_id', $userId)
            ->first();

        // If same reaction clicked again -> remove
        if ($existing && $existing->type === $data['type']) {
            $existing->delete();
        } else {
            // Else create/update reaction
            PostReaction::updateOrCreate(
                ['post_id' => $post->id, 'user_id' => $userId],
                ['type' => $data['type']]
            );
        }

        // Return JSON if AJAX
        if ($request->expectsJson()) {
            $counts = PostReaction::where('post_id', $post->id)
                ->select('type', DB::raw('COUNT(*) as total'))
                ->groupBy('type')
                ->pluck('total', 'type'); // ['like'=>3,'love'=>1...]

            $myReaction = PostReaction::where('post_id', $post->id)
                ->where('user_id', $userId)
                ->value('type');

            return response()->json([
                'ok' => true,
                'post_id' => $post->id,
                'counts' => $counts,
                'myReaction' => $myReaction, // null if none
            ]);
        }

        return back();
    }
}