<?php

namespace App\Http\Controllers\Api\V1\Filters;

use App\Http\Controllers\Controller;
use App\Models\Bookmark;
use App\Services\RecipeFilterService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class FilterBookmarkController extends Controller
{
    protected RecipeFilterService $filterService;

    public function __construct(RecipeFilterService $filterService)
    {
        $this->filterService = $filterService;
    }

    /**
     * Get all user's filter bookmarks.
     */
    public function index(): JsonResponse
    {
        $bookmarks = Bookmark::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get(['id', 'name', 'filters', 'created_at']);

        return response()->json([
            'data' => $bookmarks,
            'total' => $bookmarks->count(),
        ]);
    }

    /**
     * Store a new filter bookmark.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'filters' => 'required|array',
            'filters.tags' => 'sometimes|array',
            'filters.tags.*' => 'integer|exists:tags,id',
            'filters.ingrediente_incluir' => 'sometimes|array',
            'filters.ingrediente_incluir.*' => 'integer|exists:ingredientes,id',
            'filters.ingrediente_excluir' => 'sometimes|array',
            'filters.ingrediente_excluir.*' => 'integer|exists:ingredientes,id',
            'filters.num_ingredientes' => 'sometimes|array',
            'filters.num_ingredientes.min' => 'integer|min:0|max:10',
            'filters.num_ingredientes.max' => 'integer|min:0|max:10',
            'filters.num_tiempo' => 'sometimes|array',
            'filters.num_tiempo.min' => 'integer|min:0|max:60',
            'filters.num_tiempo.max' => 'integer|min:0|max:60',
            'filters.calorias' => 'sometimes|array',
            'filters.calorias.min' => 'integer|min:0|max:900',
            'filters.calorias.max' => 'integer|min:0|max:900',
            'filters.nutrientes' => 'sometimes|array',
            'filters.nutrientes.*.min' => 'numeric|min:0',
            'filters.nutrientes.*.max' => 'numeric|min:0',
        ]);

        $bookmark = Bookmark::create([
            'user_id' => Auth::id(),
            'name' => $validated['name'],
            'filters' => $validated['filters'],
        ]);

        return response()->json([
            'message' => 'Filter bookmark saved successfully',
            'data' => $bookmark,
        ], 201);
    }

    /**
     * Get a specific filter bookmark.
     */
    public function show(int $id): JsonResponse
    {
        $bookmark = Bookmark::where('user_id', Auth::id())
            ->findOrFail($id);

        return response()->json([
            'data' => $bookmark,
        ]);
    }

    /**
     * Update a filter bookmark.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'filters' => 'sometimes|array',
            'filters.tags' => 'sometimes|array',
            'filters.tags.*' => 'integer|exists:tags,id',
            'filters.ingrediente_incluir' => 'sometimes|array',
            'filters.ingrediente_incluir.*' => 'integer|exists:ingredientes,id',
            'filters.ingrediente_excluir' => 'sometimes|array',
            'filters.ingrediente_excluir.*' => 'integer|exists:ingredientes,id',
            'filters.num_ingredientes' => 'sometimes|array',
            'filters.num_ingredientes.min' => 'integer|min:0|max:10',
            'filters.num_ingredientes.max' => 'integer|min:0|max:10',
            'filters.num_tiempo' => 'sometimes|array',
            'filters.num_tiempo.min' => 'integer|min:0|max:60',
            'filters.num_tiempo.max' => 'integer|min:0|max:60',
            'filters.calorias' => 'sometimes|array',
            'filters.calorias.min' => 'integer|min:0|max:900',
            'filters.calorias.max' => 'integer|min:0|max:900',
            'filters.nutrientes' => 'sometimes|array',
            'filters.nutrientes.*.min' => 'numeric|min:0',
            'filters.nutrientes.*.max' => 'numeric|min:0',
        ]);

        $bookmark = Bookmark::where('user_id', Auth::id())
            ->findOrFail($id);

        $bookmark->update($validated);

        return response()->json([
            'message' => 'Filter bookmark updated successfully',
            'data' => $bookmark->fresh(),
        ]);
    }

    /**
     * Delete a filter bookmark.
     */
    public function destroy(int $id): JsonResponse
    {
        $bookmark = Bookmark::where('user_id', Auth::id())
            ->findOrFail($id);

        $bookmark->delete();

        return response()->json([
            'message' => 'Filter bookmark deleted successfully',
        ]);
    }

    /**
     * Delete multiple filter bookmarks.
     */
    public function destroyMultiple(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'bookmark_ids' => 'required|array',
            'bookmark_ids.*' => 'integer|exists:bookmarks,id',
        ]);

        $deleted = Bookmark::where('user_id', Auth::id())
            ->whereIn('id', $validated['bookmark_ids'])
            ->delete();

        return response()->json([
            'message' => "Deleted {$deleted} filter bookmarks successfully",
            'deleted_count' => $deleted,
        ]);
    }

    /**
     * Load and merge multiple filter bookmarks, then apply to recipe filtering.
     * This replicates the original getBookmark() functionality.
     */
    public function loadAndFilter(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'bookmark_ids' => 'required|array',
            'bookmark_ids.*' => 'integer|exists:bookmarks,id',
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        // Get the bookmarks
        $bookmarks = Bookmark::where('user_id', Auth::id())
            ->whereIn('id', $validated['bookmark_ids'])
            ->get();

        if ($bookmarks->isEmpty()) {
            return response()->json([
                'message' => 'No bookmarks found',
                'data' => [],
                'meta' => [
                    'total' => 0,
                ],
            ]);
        }

        // Merge filters from all bookmarks (replicating original logic)
        $mergedFilters = [];
        
        foreach ($bookmarks as $bookmark) {
            $filters = $bookmark->filters;
            
            // Merge arrays for tags and ingredients
            if (isset($filters['tags'])) {
                $mergedFilters['tags'] = array_unique(array_merge($mergedFilters['tags'] ?? [], $filters['tags']));
            }
            
            if (isset($filters['ingrediente_incluir'])) {
                $mergedFilters['ingrediente_incluir'] = array_unique(array_merge($mergedFilters['ingrediente_incluir'] ?? [], $filters['ingrediente_incluir']));
            }
            
            if (isset($filters['ingrediente_excluir'])) {
                $mergedFilters['ingrediente_excluir'] = array_unique(array_merge($mergedFilters['ingrediente_excluir'] ?? [], $filters['ingrediente_excluir']));
            }
            
            // For range filters, take the most restrictive values
            if (isset($filters['num_ingredientes'])) {
                $mergedFilters['num_ingredientes']['min'] = max(
                    $mergedFilters['num_ingredientes']['min'] ?? 0,
                    $filters['num_ingredientes']['min'] ?? 0
                );
                $mergedFilters['num_ingredientes']['max'] = min(
                    $mergedFilters['num_ingredientes']['max'] ?? 10,
                    $filters['num_ingredientes']['max'] ?? 10
                );
            }
            
            if (isset($filters['num_tiempo'])) {
                $mergedFilters['num_tiempo']['min'] = max(
                    $mergedFilters['num_tiempo']['min'] ?? 0,
                    $filters['num_tiempo']['min'] ?? 0
                );
                $mergedFilters['num_tiempo']['max'] = min(
                    $mergedFilters['num_tiempo']['max'] ?? 60,
                    $filters['num_tiempo']['max'] ?? 60
                );
            }
            
            if (isset($filters['calorias'])) {
                $mergedFilters['calorias']['min'] = max(
                    $mergedFilters['calorias']['min'] ?? 0,
                    $filters['calorias']['min'] ?? 0
                );
                $mergedFilters['calorias']['max'] = min(
                    $mergedFilters['calorias']['max'] ?? 900,
                    $filters['calorias']['max'] ?? 900
                );
            }
            
            // For nutrients, merge and take most restrictive
            if (isset($filters['nutrientes'])) {
                foreach ($filters['nutrientes'] as $nutrientId => $nutrientFilter) {
                    $mergedFilters['nutrientes'][$nutrientId]['min'] = max(
                        $mergedFilters['nutrientes'][$nutrientId]['min'] ?? 0,
                        $nutrientFilter['min'] ?? 0
                    );
                    $mergedFilters['nutrientes'][$nutrientId]['max'] = min(
                        $mergedFilters['nutrientes'][$nutrientId]['max'] ?? PHP_INT_MAX,
                        $nutrientFilter['max'] ?? PHP_INT_MAX
                    );
                }
            }
        }

        // Apply the merged filters using the filter service
        $recipes = $this->filterService->getAdvancedFilteredRecipes($mergedFilters);

        // Paginate results
        $currentPage = $request->get('page', 1);
        $perPage = $request->get('per_page', 27);
        $paginatedResults = $this->filterService->paginateCollection($recipes, $perPage, $currentPage);

        return response()->json([
            'data' => $paginatedResults['data'],
            'meta' => [
                'current_page' => $paginatedResults['current_page'],
                'per_page' => $paginatedResults['per_page'],
                'total' => $paginatedResults['total'],
                'last_page' => $paginatedResults['last_page'],
                'from' => $paginatedResults['from'],
                'to' => $paginatedResults['to'],
                'has_more_pages' => $paginatedResults['has_more_pages'],
            ],
            'merged_filters' => $mergedFilters,
            'bookmarks_used' => $bookmarks->pluck('name', 'id'),
        ]);
    }
}

