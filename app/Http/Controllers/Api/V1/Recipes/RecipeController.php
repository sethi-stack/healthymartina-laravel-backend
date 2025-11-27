<?php

namespace App\Http\Controllers\Api\V1\Recipes;

use App\Http\Controllers\Controller;
use App\Http\Resources\Recipe\RecipeResource;
use App\Models\Receta;
use App\Services\RecipeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RecipeController extends Controller
{
    protected RecipeService $recipeService;

    public function __construct(RecipeService $recipeService)
    {
        $this->recipeService = $recipeService;
    }

    /**
     * Display a listing of recipes with filtering.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only([
            'search', 'tags', 'max_calories', 'min_calories', 
            'tipo_id', 'sort_by', 'sort_order'
        ]);

        $query = $this->recipeService->getFilteredRecipes($filters);
        
        // Paginate
        $perPage = $request->get('per_page', 15);
        $recipes = $query->paginate($perPage);

        return RecipeResource::collection($recipes);
    }

    /**
     * Display the specified recipe.
     */
    public function show(string $slug): RecipeResource
    {
        $recipe = $this->recipeService->getRecipeBySlug($slug);
        return new RecipeResource($recipe);
    }

    /**
     * Search recipes using Scout/Algolia.
     */
    public function search(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'q' => 'required|string|min:2',
        ]);

        $recipes = Receta::search($request->q)
            ->query(fn ($query) => $query->with(['tags']))
            ->paginate($request->get('per_page', 15));

        return RecipeResource::collection($recipes);
    }

    /**
     * Get user's bookmarked recipes.
     */
    public function bookmarks(Request $request): AnonymousResourceCollection
    {
        $query = $this->recipeService->getUserBookmarks();
        $perPage = $request->get('per_page', 15);
        $recipes = $query->paginate($perPage);

        return RecipeResource::collection($recipes);
    }

    /**
     * Toggle bookmark for a recipe.
     */
    public function toggleBookmark(int $id): JsonResponse
    {
        $result = $this->recipeService->toggleBookmark($id);
        return response()->json($result);
    }

    /**
     * Add or update reaction to a recipe.
     */
    public function react(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'is_like' => 'required|boolean',
        ]);

        $result = $this->recipeService->addReaction($id, $validated['is_like']);
        return response()->json($result);
    }

    /**
     * Remove reaction from a recipe.
     */
    public function removeReaction(int $id): JsonResponse
    {
        $result = $this->recipeService->removeReaction($id);
        
        if ($result) {
            return response()->json(['message' => 'Reaction removed']);
        }

        return response()->json(['message' => 'No reaction found'], 404);
    }

    /**
     * Get recipe statistics.
     */
    public function stats(int $id): JsonResponse
    {
        $stats = $this->recipeService->getRecipeStats($id);
        return response()->json($stats);
    }

    /**
     * Get similar recipes.
     */
    public function similar(int $id): AnonymousResourceCollection
    {
        $recipes = $this->recipeService->getSimilarRecipes($id);
        return RecipeResource::collection($recipes);
    }

    /**
     * Get popular recipes.
     */
    public function popular(Request $request): AnonymousResourceCollection
    {
        $limit = $request->get('limit', 10);
        $days = $request->get('days', 30);
        
        $recipes = $this->recipeService->getPopularRecipes($limit, $days);
        return RecipeResource::collection($recipes);
    }
}

