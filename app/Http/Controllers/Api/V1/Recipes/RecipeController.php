<?php

namespace App\Http\Controllers\Api\V1\Recipes;

use App\Http\Controllers\Controller;
use App\Http\Resources\Recipe\RecipeResource;
use App\Models\Receta;
use App\Services\RecipeService;
use App\Services\RecipeFilterService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RecipeController extends Controller
{
    protected RecipeService $recipeService;
    protected RecipeFilterService $filterService;

    public function __construct(RecipeService $recipeService, RecipeFilterService $filterService)
    {
        $this->recipeService = $recipeService;
        $this->filterService = $filterService;
    }

    /**
     * Display a listing of recipes with filtering.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only([
            'search', 'tags', 'max_calories', 'min_calories', 
            'sort_by', 'sort_order'
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

    /**
     * Advanced recipe filtering with complex logic (replaces recetario() method).
     */
    public function advancedFilter(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tags' => 'sometimes|array',
            'tags.*' => 'integer|exists:tags,id',
            'ingrediente_incluir' => 'sometimes|array',
            'ingrediente_incluir.*' => 'integer|exists:ingredientes,id',
            'ingrediente_excluir' => 'sometimes|array',
            'ingrediente_excluir.*' => 'integer|exists:ingredientes,id',
            'num_ingredientes' => 'sometimes|array',
            'num_ingredientes.min' => 'integer|min:0|max:10',
            'num_ingredientes.max' => 'integer|min:0|max:10',
            'num_tiempo' => 'sometimes|array',
            'num_tiempo.min' => 'integer|min:0|max:60',
            'num_tiempo.max' => 'integer|min:0|max:60',
            'calorias' => 'sometimes|array',
            'calorias.min' => 'integer|min:0|max:900',
            'calorias.max' => 'integer|min:0|max:900',
            'nutrientes' => 'sometimes|array',
            'nutrientes.*.min' => 'numeric|min:0',
            'nutrientes.*.max' => 'numeric|min:0',
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        // Get filtered recipes using the advanced filter service
        $recipes = $this->filterService->getAdvancedFilteredRecipes($validated);

        // Paginate results
        $currentPage = $request->get('page', 1);
        $perPage = $request->get('per_page', 27);
        $paginatedResults = $this->filterService->paginateCollection($recipes, $perPage, $currentPage);

        // Transform to resources
        $resourceCollection = RecipeResource::collection($paginatedResults['data']);

        return response()->json([
            'data' => $resourceCollection,
            'meta' => [
                'current_page' => $paginatedResults['current_page'],
                'per_page' => $paginatedResults['per_page'],
                'total' => $paginatedResults['total'],
                'last_page' => $paginatedResults['last_page'],
                'from' => $paginatedResults['from'],
                'to' => $paginatedResults['to'],
                'has_more_pages' => $paginatedResults['has_more_pages'],
            ],
            'filters_applied' => $validated,
            'total_filtered' => $paginatedResults['total'],
        ]);
    }

    /**
     * Get filter metadata for advanced filtering.
     */
    public function filterMetadata(): JsonResponse
    {
        $metadata = $this->filterService->getFilterMetadata();
        return response()->json($metadata);
    }

    /**
     * Track recipe view (for analytics).
     */
    public function trackView(int $id): JsonResponse
    {
        $recipe = Receta::findOrFail($id);
        
        // Here you could implement view tracking logic
        // For now, we'll just return success
        // In a real implementation, you might:
        // - Log to analytics service
        // - Increment view counter
        // - Track user viewing patterns
        
        return response()->json([
            'message' => 'View tracked',
            'recipe_id' => $recipe->id,
            'recipe_title' => $recipe->titulo,
        ]);
    }
}

