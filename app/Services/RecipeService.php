<?php

namespace App\Services;

use App\Models\Receta;
use App\Models\Bookmark;
use App\Models\Reaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Collection;

class RecipeService
{
    private function getBookmarkRecipeKey(): ?string
    {
        if (Schema::hasColumn('bookmarks', 'recipe_id')) {
            return 'recipe_id';
        }

        if (Schema::hasColumn('bookmarks', 'receta_id')) {
            return 'receta_id';
        }

        return null;
    }

    /**
     * Get recipes with advanced filtering and relationships.
     */
    public function getFilteredRecipes(array $filters = [])
    {
        $query = Receta::query();

        // Apply filters
        if (isset($filters['search'])) {
            $query->where('titulo', 'like', '%' . $filters['search'] . '%');
        }

        if (isset($filters['tags']) && !empty($filters['tags'])) {
            $tagIds = is_array($filters['tags']) ? $filters['tags'] : explode(',', $filters['tags']);
            $query->whereHas('tags', function ($q) use ($tagIds) {
                $q->whereIn('tags.id', $tagIds);
            });
        }

        if (isset($filters['max_calories'])) {
            $query->where('calories', '<=', $filters['max_calories']);
        }

        if (isset($filters['min_calories'])) {
            $query->where('calories', '>=', $filters['min_calories']);
        }

        // Eager load relationships
        $query->with(['tags']);

        // Sort
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query;
    }

    /**
     * Get recipe by slug with all relationships.
     */
    public function getRecipeBySlug(string $slug)
    {
        return Receta::where('slug', $slug)
            ->with([
                'tags',
                'comments' => function ($query) {
                    $query->orderBy('created_at', 'desc');
                },
                'reactions',
            ])
            ->firstOrFail();
    }

    /**
     * Check if user has bookmarked a recipe.
     */
    public function isBookmarked(int $recipeId, ?int $userId = null): bool
    {
        $userId = $userId ?? Auth::id();
        $bookmarkRecipeKey = $this->getBookmarkRecipeKey();
        
        if (!$userId || !$bookmarkRecipeKey) {
            return false;
        }

        return Bookmark::where($bookmarkRecipeKey, $recipeId)
            ->where('user_id', $userId)
            ->exists();
    }

    /**
     * Toggle bookmark for a recipe.
     */
    public function toggleBookmark(int $recipeId, ?int $userId = null): array
    {
        $userId = $userId ?? Auth::id();
        $bookmarkRecipeKey = $this->getBookmarkRecipeKey();

        if (!$bookmarkRecipeKey) {
            return ['bookmarked' => false, 'message' => 'Bookmark recipe key not configured'];
        }

        $bookmark = Bookmark::where($bookmarkRecipeKey, $recipeId)
            ->where('user_id', $userId)
            ->first();

        if ($bookmark) {
            $bookmark->delete();
            return ['bookmarked' => false, 'message' => 'Bookmark removed'];
        }

        Bookmark::create([$bookmarkRecipeKey => $recipeId, 'user_id' => $userId]);

        return ['bookmarked' => true, 'message' => 'Bookmark added'];
    }

    /**
     * Get user's bookmarked recipes.
     */
    public function getUserBookmarks(?int $userId = null)
    {
        $userId = $userId ?? Auth::id();
        $bookmarkRecipeKey = $this->getBookmarkRecipeKey();

        if (!$bookmarkRecipeKey) {
            return Receta::query()->whereRaw('1 = 0');
        }

        return Receta::whereIn('id', function ($query) use ($userId, $bookmarkRecipeKey) {
            $query->select($bookmarkRecipeKey)
                ->from('bookmarks')
                ->where('user_id', $userId)
                ->whereNull('deleted_at');
        })->with(['tags']);
    }

    /**
     * Add or update reaction to a recipe.
     */
    public function addReaction(int $recipeId, bool $isLike, ?int $userId = null): array
    {
        $userId = $userId ?? Auth::id();

        // Check if user already reacted
        $reaction = Reaction::where('recipe_id', $recipeId)
            ->where('user_id', $userId)
            ->first();

        if ($reaction) {
            // Update existing reaction
            $reaction->update(['is_like' => $isLike]);
            return ['updated' => true, 'is_like' => $isLike];
        }

        // Create new reaction
        Reaction::create([
            'recipe_id' => $recipeId,
            'user_id' => $userId,
            'is_like' => $isLike,
        ]);

        return ['created' => true, 'is_like' => $isLike];
    }

    /**
     * Remove reaction from a recipe.
     */
    public function removeReaction(int $recipeId, ?int $userId = null): bool
    {
        $userId = $userId ?? Auth::id();

        $reaction = Reaction::where('recipe_id', $recipeId)
            ->where('user_id', $userId)
            ->first();

        if ($reaction) {
            $reaction->delete();
            return true;
        }

        return false;
    }

    /**
     * Get recipe statistics.
     */
    public function getRecipeStats(int $recipeId): array
    {
        $recipe = Receta::findOrFail($recipeId);

        $likes = Reaction::where('recipe_id', $recipeId)
            ->where('is_like', true)
            ->count();

        $dislikes = Reaction::where('recipe_id', $recipeId)
            ->where('is_like', false)
            ->count();

        $bookmarkRecipeKey = $this->getBookmarkRecipeKey();
        $bookmarks = $bookmarkRecipeKey
            ? Bookmark::where($bookmarkRecipeKey, $recipeId)->count()
            : 0;

        $comments = $recipe->comments()->count();

        return [
            'likes' => $likes,
            'dislikes' => $dislikes,
            'bookmarks' => $bookmarks,
            'comments' => $comments,
            'total_reactions' => $likes + $dislikes,
        ];
    }

    /**
     * Get similar recipes based on tags.
     */
    public function getSimilarRecipes(int $recipeId, int $limit = 6): Collection
    {
        $recipe = Receta::with('tags')->findOrFail($recipeId);
        $tagIds = $recipe->tags->pluck('id')->toArray();

        if (empty($tagIds)) {
            // If no tags, return recent recipes
            return Receta::where('id', '!=', $recipeId)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
        }

        // Find recipes with similar tags
        return Receta::where('id', '!=', $recipeId)
            ->whereHas('tags', function ($query) use ($tagIds) {
                $query->whereIn('tags.id', $tagIds);
            })
            ->withCount(['tags' => function ($query) use ($tagIds) {
                $query->whereIn('tags.id', $tagIds);
            }])
            ->orderBy('tags_count', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get popular recipes (most reactions/bookmarks).
     */
    public function getPopularRecipes(int $limit = 10, int $days = 30)
    {
        $since = now()->subDays($days);
        $bookmarkRecipeKey = $this->getBookmarkRecipeKey();

        $query = Receta::withCount([
            'reactions' => function ($q) use ($since) {
                $q->where('created_at', '>=', $since);
            },
        ]);

        if ($bookmarkRecipeKey) {
            $query->addSelect([
                'bookmarks_count' => Bookmark::query()
                    ->selectRaw('count(*)')
                    ->whereColumn("bookmarks.{$bookmarkRecipeKey}", 'recetas.id')
                    ->where('bookmarks.created_at', '>=', $since)
                    ->whereNull('bookmarks.deleted_at'),
            ]);
        } else {
            $query->selectRaw('recetas.*, 0 as bookmarks_count');
        }

        return $query
            ->orderByDesc('reactions_count')
            ->orderByDesc('bookmarks_count')
            ->limit($limit)
            ->get();
    }
}
