<?php

namespace App\Http\Controllers\Api\V1\Recipes;

use App\Http\Controllers\Controller;
use App\Http\Resources\Recipe\RecipeResource;
use App\Models\Receta;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RecipeController extends Controller
{
    /**
     * Display a listing of recipes with filtering.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Receta::with(['tags']);

        // Search by title
        if ($request->has('search')) {
            $query->where('titulo', 'like', '%' . $request->search . '%');
        }

        // Filter by tags
        if ($request->has('tags')) {
            $tagIds = is_array($request->tags) ? $request->tags : explode(',', $request->tags);
            $query->whereHas('tags', function ($q) use ($tagIds) {
                $q->whereIn('tags.id', $tagIds);
            });
        }

        // Filter by calories range
        if ($request->has('max_calories')) {
            $query->where('calories', '<=', $request->max_calories);
        }

        if ($request->has('min_calories')) {
            $query->where('calories', '>=', $request->min_calories);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

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
        $recipe = Receta::where('slug', $slug)
            ->with(['tags', 'comments'])
            ->firstOrFail();

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
}

