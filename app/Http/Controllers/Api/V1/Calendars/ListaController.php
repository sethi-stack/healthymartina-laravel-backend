<?php

namespace App\Http\Controllers\Api\V1\Calendars;

use App\Http\Controllers\Controller;
use App\Http\Resources\Lista\CategoryResource;
use App\Http\Resources\Lista\ListaItemResource;
use App\Models\Calendar;
use App\Models\Categoria;
use App\Models\ListaIngredientes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ListaController extends Controller
{
    /**
     * Get all ingredients for a calendar's lista.
     * Returns ingredients grouped by categories with taken status.
     */
    public function index(Request $request, int $calendarId): JsonResponse
    {
        $calendar = Calendar::where('id', $calendarId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        // Get taken ingredients
        $takenIngredients = DB::table('lista_ingrediente_taken')
            ->where('calendario_id', $calendar->id)
            ->get();

        // Get categories
        $categorias = Categoria::orderBy('sort', 'ASC')->get();

        // Get custom lista ingredients
        $listaIngredientes = ListaIngredientes::where('calendario_id', $calendar->id)->get();

        // Get all ingredients grouped by category using helper function
        $ingredients = [];
        $totalCount = 0;
        
        foreach ($categorias as $category) {
            $categoryIngredients = getRelatedIngrediente($calendar->id, $category->id, 'list');
            $ingredients[$category->id] = $categoryIngredients;
            $totalCount += count($categoryIngredients);
        }

        return response()->json([
            'calendar' => [
                'id' => $calendar->id,
                'title' => $calendar->title,
            ],
            'categories' => CategoryResource::collection($categorias),
            'ingredients' => $ingredients,
            'taken_ingredients' => $takenIngredients,
            'custom_items' => ListaItemResource::collection($listaIngredientes),
            'total_count' => $totalCount,
        ]);
    }

    /**
     * Get ingredients for a specific category.
     */
    public function category(Request $request, int $calendarId, int $categoryId): JsonResponse
    {
        $calendar = Calendar::where('id', $calendarId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        // Get taken ingredients for this calendar
        $takenIngredients = DB::table('lista_ingrediente_taken')
            ->where('calendario_id', $calendar->id)
            ->get();

        // Get the specific category
        $category = Categoria::findOrFail($categoryId);

        // Get custom lista ingredients for this category
        $listaIngredientes = ListaIngredientes::where('categoria', $categoryId)
            ->where('calendario_id', $calendar->id)
            ->get();

        // Get recipe ingredients for this category
        $ingredients = getRelatedIngrediente($calendar->id, $categoryId, 'list');

        // Sort ingredients by calendar day labels if available
        $ingredientsDataSorted = [];
        if ($calendar->labels) {
            $calendarLabels = json_decode($calendar->labels, true);
            if (isset($calendarLabels['days'])) {
                foreach ($calendarLabels['days'] as $dayKey => $dayValue) {
                    foreach ($ingredients as $ingredient) {
                        if ($ingredient['day'] == $dayKey) {
                            $ingredientsDataSorted[$dayKey][] = $ingredient;
                            // Include repeat entries
                            if (isset($ingredient['repeat'])) {
                                foreach ($ingredient['repeat'] as $repeat) {
                                    if ($dayKey == $repeat['day']) {
                                        $ingredientsDataSorted[$dayKey][] = $repeat;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return response()->json([
            'calendar_id' => $calendar->id,
            'category' => new CategoryResource($category),
            'ingredients' => $ingredients,
            'ingredients_sorted' => $ingredientsDataSorted,
            'taken_ingredients' => $takenIngredients,
            'custom_items' => ListaItemResource::collection($listaIngredientes),
            'count' => count($ingredients),
        ]);
    }

    /**
     * Toggle an ingredient as taken/unchecked.
     */
    public function toggleTaken(Request $request, int $calendarId): JsonResponse
    {
        $calendar = Calendar::where('id', $calendarId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $validated = $request->validate([
            'categoria_id' => 'required|integer|exists:categorias,id',
            'ingrediente_id' => 'required|integer',
            'ingrediente_type' => 'required|string',
        ]);

        $taken = DB::table('lista_ingrediente_taken')
            ->where([
                'calendario_id' => $calendar->id,
                'categoria_id' => $validated['categoria_id'],
                'ingrediente_id' => $validated['ingrediente_id'],
                'ingrediente_type' => $validated['ingrediente_type'],
            ]);

        $action = 'created';
        
        if ($taken->exists()) {
            $taken->delete();
            $action = 'deleted';
        } else {
            DB::table('lista_ingrediente_taken')->insert([
                'calendario_id' => $calendar->id,
                'categoria_id' => $validated['categoria_id'],
                'ingrediente_id' => $validated['ingrediente_id'],
                'ingrediente_type' => $validated['ingrediente_type'],
            ]);
        }

        // Get updated taken list
        $takenIngredients = DB::table('lista_ingrediente_taken')
            ->where('calendario_id', $calendar->id)
            ->get();

        return response()->json([
            'success' => true,
            'action' => $action,
            'taken_ingredients' => $takenIngredients,
            'message' => $action === 'created' ? 'Ingredient marked as taken' : 'Ingredient unmarked',
        ]);
    }

    /**
     * Create a custom lista ingredient.
     */
    public function storeCustom(Request $request, int $calendarId): JsonResponse
    {
        $calendar = Calendar::where('id', $calendarId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $validated = $request->validate([
            'cantidad' => 'required|numeric',
            'nombre' => 'required|string|max:255',
            'categoria' => 'required|integer|exists:categorias,id',
        ]);

        $listaItem = ListaIngredientes::create([
            'calendario_id' => $calendar->id,
            'cantidad' => $validated['cantidad'],
            'nombre' => $validated['nombre'],
            'categoria' => $validated['categoria'],
        ]);

        return response()->json([
            'success' => true,
            'item' => new ListaItemResource($listaItem),
            'message' => 'Custom ingredient added successfully',
        ], 201);
    }

    /**
     * Update a custom lista ingredient.
     */
    public function updateCustom(Request $request, int $calendarId, int $itemId): JsonResponse
    {
        $calendar = Calendar::where('id', $calendarId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $validated = $request->validate([
            'cantidad' => 'required|numeric',
            'nombre' => 'required|string|max:255',
            'categoria' => 'required|integer|exists:categorias,id',
        ]);

        $listaItem = ListaIngredientes::where('id', $itemId)
            ->where('calendario_id', $calendar->id)
            ->firstOrFail();

        $listaItem->update($validated);

        return response()->json([
            'success' => true,
            'item' => new ListaItemResource($listaItem),
            'message' => 'Custom ingredient updated successfully',
        ]);
    }

    /**
     * Delete a custom lista ingredient.
     */
    public function destroyCustom(int $calendarId, int $itemId): JsonResponse
    {
        $calendar = Calendar::where('id', $calendarId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $listaItem = ListaIngredientes::where('id', $itemId)
            ->where('calendario_id', $calendar->id)
            ->firstOrFail();

        $listaItem->delete();

        return response()->json([
            'success' => true,
            'message' => 'Custom ingredient deleted successfully',
        ]);
    }
}

