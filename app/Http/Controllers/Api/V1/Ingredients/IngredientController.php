<?php

namespace App\Http\Controllers\Api\V1\Ingredients;

use App\Http\Controllers\Controller;
use App\Http\Resources\Ingredient\IngredientResource;
use App\Http\Resources\Ingredient\InstruccionResource;
use App\Models\Ingrediente;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class IngredientController extends Controller
{
    /**
     * Display a listing of ingredients with search.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Ingrediente::query();

        // Search by name
        if ($request->has('q')) {
            $query->where('nombre', 'like', '%' . $request->q . '%');
        }

        // Filter by category
        if ($request->has('categoria_id')) {
            $query->where('categoria_id', $request->categoria_id);
        }

        // Eager load relationships
        $query->with(['categoria']);

        // Sort
        $sortBy = $request->get('sort_by', 'nombre');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginate
        $perPage = $request->get('per_page', 10);
        $ingredients = $query->paginate($perPage);

        return IngredientResource::collection($ingredients);
    }

    /**
     * Display the specified ingredient.
     */
    public function show(int $id): IngredientResource
    {
        $ingredient = Ingrediente::with(['categoria', 'instrucciones'])->findOrFail($id);
        return new IngredientResource($ingredient);
    }

    /**
     * Get instructions for a specific ingredient.
     */
    public function instrucciones(int $id): AnonymousResourceCollection
    {
        $ingredient = Ingrediente::findOrFail($id);
        $instrucciones = $ingredient->instrucciones()
            ->withCount('rir')
            ->get();

        return InstruccionResource::collection($instrucciones);
    }
}

