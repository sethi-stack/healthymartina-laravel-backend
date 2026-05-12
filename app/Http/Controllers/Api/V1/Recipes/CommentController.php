<?php

namespace App\Http\Controllers\Api\V1\Recipes;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Receta;
use App\Models\User;
use App\Notifications\CommentAddedNotification;
use App\Notifications\CommentAnsweredNotification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CommentController extends Controller
{
    /**
     * Add a comment to a recipe.
     */
    public function store(Request $request, int $recipeId): JsonResponse
    {
        $validated = $request->validate([
            'comment' => 'required|string',
            'responding_comment' => 'nullable|integer|exists:comments,id',
        ]);

        $user = Auth::user();
        $receta = Receta::findOrFail($recipeId);
        
        // If responding to another comment
        if (isset($validated['responding_comment'])) {
            $parentComment = Comment::find($validated['responding_comment']);
            $parentComment->answered = 1;
            $parentComment->save();

            // Notify the original commenter
            if ($parentComment->user->preference && $parentComment->user->preference->mentions) {
                try {
                    $parentComment->user->notify(new CommentAnsweredNotification($receta));
                } catch (\Throwable $e) {
                    Log::warning('comments.notify_parent_failed', [
                        'recipe_id' => $receta->id,
                        'comment_id' => $parentComment->id,
                        'parent_user_id' => $parentComment->user_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        // Determine if comment is from admin
        $isFromAdmin = in_array($user->id, [2, 3]);

        // Create comment
        $comment = Comment::create([
            'comment' => $validated['comment'],
            'user_id' => $user->id,
            'is_a_response' => substr($validated['comment'], 0, 1) == '@' ? 1 : 0,
            'receta_id' => $receta->id,
            'from_admin' => $isFromAdmin ? 1 : 0,
        ]);

        // Notify admin about new comment (user ID 2)
        if ($comment->id) {
            $admin = User::find(2);
            if ($admin) {
                try {
                    $admin->notify(new CommentAddedNotification($receta));
                } catch (\Throwable $e) {
                    Log::warning('comments.notify_admin_failed', [
                        'recipe_id' => $receta->id,
                        'comment_id' => $comment->id,
                        'admin_user_id' => 2,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        // Associate comment with recipe
        $receta->comments()->syncWithoutDetaching($comment);

        return response()->json([
            'success' => true,
            'comment' => [
                'id' => $comment->id,
                'author' => $comment->user->name,
                'comment' => $comment->comment,
                'time' => $comment->elapsed_time,
                'from_admin' => $comment->from_admin,
                'is_a_response' => $comment->is_a_response,
            ],
        ], 201);
    }

    /**
     * Delete a comment.
     */
    public function destroy(int $commentId): JsonResponse
    {
        $comment = Comment::findOrFail($commentId);
        
        // Check if user owns the comment or is admin
        if ($comment->user_id !== Auth::id() && !in_array(Auth::id(), [2, 3])) {
            return response()->json([
                'error' => 'Unauthorized to delete this comment',
            ], 403);
        }

        $comment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Comment deleted successfully',
        ]);
    }

    /**
     * Get comments for a recipe.
     */
    public function index(int $recipeId): JsonResponse
    {
        $receta = Receta::findOrFail($recipeId);
        
        $comments = $receta->comments()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($comment) {
                return [
                    'id' => $comment->id,
                    'author' => $comment->user->name,
                    'author_id' => $comment->user_id,
                    'comment' => $comment->comment,
                    'time' => $comment->elapsed_time,
                    'from_admin' => $comment->from_admin,
                    'is_a_response' => $comment->is_a_response,
                    'answered' => $comment->answered,
                    'created_at' => $comment->created_at,
                ];
            });

        return response()->json([
            'comments' => $comments,
        ]);
    }
}
