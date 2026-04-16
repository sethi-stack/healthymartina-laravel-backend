<?php

namespace App\Services;

use App\Models\Receta;
use App\Models\Tag;
use App\Models\Ingrediente;
use App\Models\Nutriente;
use App\Models\NutrientType;
use App\Models\RecetaInstruccionReceta;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class RecipeFilterService
{
    /**
     * Apply advanced filtering to recipes based on the original recetario() logic.
     */
    public function getAdvancedFilteredRecipes(array $filters = []): Collection
    {
        $query = Receta::query();
        $filteredNutrients = [];

        // Apply tag filters (AND logic - all tags must be present)
        if (isset($filters['tags']) && !empty($filters['tags'])) {
            foreach ($filters['tags'] as $tagId) {
                $query->whereHas('tags', function (Builder $query) use ($tagId) {
                    $query->where('tag_id', $tagId);
                });
            }
        }

        // Apply number of ingredients filter
        if (isset($filters['num_ingredientes'])) {
            if (isset($filters['num_ingredientes']['min']) && $filters['num_ingredientes']['min'] > 0) {
                $query->has('recetaInstruccionReceta', '>=', $filters['num_ingredientes']['min']);
            }
            if (isset($filters['num_ingredientes']['max']) && $filters['num_ingredientes']['max'] < 10) {
                $query->has('recetaInstruccionReceta', '<=', $filters['num_ingredientes']['max']);
            }
        }

        // Apply cooking time filter
        if (isset($filters['num_tiempo'])) {
            if (isset($filters['num_tiempo']['min']) && $filters['num_tiempo']['min'] > 0) {
                $query->where('tiempo', '>=', $filters['num_tiempo']['min']);
            }
            if (isset($filters['num_tiempo']['max']) && $filters['num_tiempo']['max'] < 60) {
                $query->where('tiempo', '<=', $filters['num_tiempo']['max']);
            }
        }

        // Apply calories filter (JSON column query)
        if (isset($filters['calorias'])) {
            if (isset($filters['calorias']['min']) && $filters['calorias']['min'] != 0) {
                $filteredNutrients[] = 1008;
                $query->where("nutrient_info->1008->cantidad", '>=', (int) $filters['calorias']['min']);
            }
            if (isset($filters['calorias']['max']) && $filters['calorias']['max'] != 900) {
                $filteredNutrients[] = 1008;
                $query->where("nutrient_info->1008->cantidad", '<=', (int) $filters['calorias']['max']);
            }
        }

        // Apply nutrient filters (30+ nutrients with JSON queries)
        if (isset($filters['nutrientes']) && !empty($filters['nutrientes'])) {
            foreach ($filters['nutrientes'] as $fdcId => $nutrientFilter) {
                $nutrient = Nutriente::where('fdc_id', $fdcId)->first();
                
                if ($nutrient && $fdcId != 0) {
                    // Apply minimum filter
                    if (isset($nutrientFilter['min']) && $nutrientFilter['min'] > 0) {
                        $filteredNutrients[] = $fdcId;
                        $minValue = $nutrient->factor != 0 
                            ? (int) ($nutrientFilter['min'] / $nutrient->factor)
                            : (int) $nutrientFilter['min'];
                        
                        $query->where('nutrient_info->' . $fdcId . '->cantidad', '>', $minValue);
                    }
                    
                    // Apply maximum filter
                    if (isset($nutrientFilter['max']) && floor($nutrientFilter['max']) < floor($nutrient->cien_porciento)) {
                        $filteredNutrients[] = $fdcId;
                        $maxValue = $nutrient->factor != 0 
                            ? (int) ($nutrientFilter['max'] / $nutrient->factor)
                            : (int) $nutrientFilter['max'];
                        
                        $query->where('nutrient_info->' . $fdcId . '->cantidad', '<=', $maxValue);
                    }
                }
            }
        }

        // Apply ingredient filters before materializing the result set so we keep the
        // candidate pool small and avoid recursion-heavy scans across the entire table.
        if (isset($filters['ingrediente_excluir']) && !empty($filters['ingrediente_excluir'])) {
            $query->whereDoesntHave('recetaInstruccionReceta.instruccion.ingrediente', function (Builder $query) use ($filters) {
                $query->whereIn('id', $filters['ingrediente_excluir']);
            });
        }

        if (isset($filters['ingrediente_incluir']) && !empty($filters['ingrediente_incluir'])) {
            $query->whereHas('recetaInstruccionReceta.instruccion.ingrediente', function (Builder $query) use ($filters) {
                $query->whereIn('id', $filters['ingrediente_incluir']);
            });
        }

        $query->orderBy('recetas.id', 'desc');
        $recipes = $query->get();

        // Post-query filtering for complex ingredient logic
        if (isset($filters['ingrediente_excluir']) && !empty($filters['ingrediente_excluir'])) {
            $recipes = $this->filterByExcludedIngredients($recipes, $filters['ingrediente_excluir']);
        }

        if (isset($filters['ingrediente_incluir']) && !empty($filters['ingrediente_incluir'])) {
            $recipes = $this->filterByIncludedIngredients($recipes, $filters['ingrediente_incluir'], $filters);
        }

        return $recipes;
    }

    /**
     * Filter recipes by excluded ingredients, including subrecipe logic.
     */
    private function filterByExcludedIngredients(Collection $recipes, array $excludedIngredients): Collection
    {
        $filteredRecipes = $recipes->filter(function ($recipe) use ($excludedIngredients) {
            // Check subrecipes for excluded ingredients
            $matchingChildren = RecetaInstruccionReceta::where('receta_id', $recipe->id)
                ->whereNotNull('subreceta_id')
                ->get(['subreceta_id'])
                ->toArray();

            $childIds = array_map(function ($id) {
                return $id['subreceta_id'];
            }, $matchingChildren);

            $children = Receta::whereIn('id', $childIds)->get();

            foreach ($children as $child) {
                $childIngredients = array_map(function ($ingredients) {
                    return $ingredients['ingrediente_id'] ?? null;
                }, $child->getIngredientesIds());

                $childIngredients = array_filter($childIngredients);

                if (count(array_intersect($childIngredients, $excludedIngredients)) > 0) {
                    return false;
                }
            }

            return true;
        });

        $ids = $filteredRecipes->pluck('id')->unique()->values()->all();

        return empty($ids) ? Receta::whereRaw('1 = 0')->get() : Receta::findMany($ids);
    }

    /**
     * Filter recipes by included ingredients with "ALL must be present" logic and parent/child relationships.
     */
    private function filterByIncludedIngredients(Collection $recipes, array $includedIngredients, array $filters): Collection
    {
        $filteredRecipes = collect();

        foreach ($recipes as $recipe) {
            $recipeIngredients = array_map(function ($ingredients) {
                return $ingredients['ingrediente_id'] ?? null;
            }, $recipe->getIngredientesIds());

            $recipeIngredients = array_filter($recipeIngredients);

            if (count(array_intersect($recipeIngredients, $includedIngredients)) == count($includedIngredients)) {
                $filteredRecipes->push($recipe);

                $matchingParents = RecetaInstruccionReceta::where('subreceta_id', $recipe->id)
                    ->get(['receta_id'])
                    ->toArray();

                $parentIds = array_map(function ($id) {
                    return $id['receta_id'];
                }, $matchingParents);

                if (isset($filters['tags']) && (in_array("18", $filters['tags']) || in_array("25", $filters['tags']))) {
                    $parents = Receta::whereIn('id', $parentIds)
                        ->whereHas('tags', function ($query) {
                            $query->whereIn('tag_id', [18, 25]);
                        })->get();
                } else {
                    $parents = Receta::findMany($parentIds);
                }

                foreach ($parents as $parent) {
                    $filteredRecipes->push($parent);
                }
            } else {
                $matchingIngredients = array_intersect($recipeIngredients, $includedIngredients);
                $parentRecipe = $this->checkIfCombinedWithParentsIncludeAll($recipe, $includedIngredients, $matchingIngredients);

                if ($parentRecipe) {
                    $filteredRecipes->push($parentRecipe);
                }
            }
        }

        $ids = $filteredRecipes->unique('id')->pluck('id')->values()->all();

        return empty($ids) ? Receta::whereRaw('1 = 0')->get() : Receta::findMany($ids);
    }

    /**
     * Check if a recipe combined with its parent recipes satisfies the "include all ingredients" requirement.
     * This is a direct port of the original checkIfCombinedWithParentsIncludeAll method.
     */
    private function checkIfCombinedWithParentsIncludeAll($recipe, array $includeIngredients, array $mergeIngredients)
    {
        $parents = RecetaInstruccionReceta::where('subreceta_id', $recipe->id)
            ->get(['receta_id'])
            ->toArray();

        if (count($parents) == 0) {
            return false;
        }

        foreach ($parents as $parent) {
            $parentRecipe = Receta::find($parent['receta_id']);
            
            if ($parentRecipe) {
                $parentIngredients = array_map(function ($ingredients) {
                    return $ingredients['ingrediente_id'] ?? null;
                }, $parentRecipe->getIngredientesIds());

                $parentIngredients = array_filter($parentIngredients);

                // Check if combined ingredients satisfy the requirement
                $combinedIngredients = array_unique(array_merge($parentIngredients, $mergeIngredients));
                
                if (count(array_intersect($combinedIngredients, $includeIngredients)) == count($includeIngredients)) {
                    return $parentRecipe;
                }
            }
        }

        return null;
    }

    // No recursive full-table flattening here: the bounded legacy-style candidate set
    // keeps the advanced filter from exhausting memory on large recipe catalogs.

    /**
     * Get default filter values (from original bookmark JSON).
     */
    public function getDefaultFilters(): array
    {
        return [
            'tags' => [],
            'num_ingredientes' => ['min' => 0, 'max' => 10],
            'num_tiempo' => ['min' => 0, 'max' => 60],
            'calorias' => ['min' => 0, 'max' => 900],
            'nutrientes' => [
                '1005' => ['min' => 0, 'max' => 130],
                '1079' => ['min' => 0, 'max' => 30],
                '2000' => ['min' => 0, 'max' => 25],
                '1003' => ['min' => 0, 'max' => 46],
                '1004' => ['min' => 0, 'max' => 60],
                '1258' => ['min' => 0, 'max' => 22],
                '1292' => ['min' => 0, 'max' => 44],
                '1293' => ['min' => 0, 'max' => 22],
                '1253' => ['min' => 0, 'max' => 300],
                '1087' => ['min' => 0, 'max' => 1000],
                '1089' => ['min' => 0, 'max' => 18],
                '1090' => ['min' => 0, 'max' => 320],
                '1091' => ['min' => 0, 'max' => 700],
                '1092' => ['min' => 0, 'max' => 4700],
                '1093' => ['min' => 0, 'max' => 1500],
                '1095' => ['min' => 0, 'max' => 8],
                '1103' => ['min' => 0, 'max' => 55],
                '1104' => ['min' => 0, 'max' => 3000],
                '1165' => ['min' => 0, 'max' => 1],
                '1166' => ['min' => 0, 'max' => 1],
                '1167' => ['min' => 0, 'max' => 14],
                '1175' => ['min' => 0, 'max' => 1],
                '1178' => ['min' => 0, 'max' => 2],
                '1162' => ['min' => 0, 'max' => 75],
                '1110' => ['min' => 0, 'max' => 15],
                '1109' => ['min' => 0, 'max' => 15],
                '1185' => ['min' => 0, 'max' => 90],
                '1183' => ['min' => 0, 'max' => 90],
                '1177' => ['min' => 0, 'max' => 400],
                '1180' => ['min' => 0, 'max' => 425],
            ]
        ];
    }

    /**
     * Get filter metadata for frontend.
     */
    public function getFilterMetadata(): array
    {
        return [
            'tags' => Tag::all(['id', 'nombre']),
            'ingredientes' => Ingrediente::all(['id', 'nombre']),
            'nutrient_types' => NutrientType::with('nutrientes')->orderBy('id', 'DESC')->get(),
            'defaults' => $this->getDefaultFilters(),
        ];
    }

    /**
     * Paginate a collection manually (from original paginate method).
     */
    public function paginateCollection(Collection $collection, int $perPage = 27, int $currentPage = 1): array
    {
        $total = $collection->count();
        $offset = ($currentPage - 1) * $perPage;
        $items = $collection->slice($offset, $perPage)->values();

        $lastPage = ceil($total / $perPage);

        return [
            'data' => $items,
            'current_page' => $currentPage,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => $lastPage,
            'from' => $offset + 1,
            'to' => min($offset + $perPage, $total),
            'has_more_pages' => $currentPage < $lastPage,
        ];
    }
}
