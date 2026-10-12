<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostVote;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VoteController extends Controller
{
    public function store(Request $request, Post $post): RedirectResponse
    {
        $validated = $request->validate(['value' => 'required|in:-1,1']);
        $value = (int) $validated['value'];

        $existing = PostVote::where('user_id', $request->user()->id)
            ->where('post_id', $post->id)
            ->first();

        if ($existing) {
            if ($existing->value === $value) {
                $existing->delete();

                return back()->with('success', 'Vote removed.');
            }

            $existing->update(['value' => $value]);

            return back()->with('success', 'Vote updated.');
        }

        try {
            PostVote::create([
                'user_id' => $request->user()->id,
                'post_id' => $post->id,
                'value' => $value,
            ]);
        } catch (QueryException) {
            // Unique (post_id, user_id) violation — a concurrent request already voted.
        }

        return back()->with('success', 'Vote recorded.');
    }
}
