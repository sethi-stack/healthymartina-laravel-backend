<?php

namespace App\Http\Resources\Recipe;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class RecipeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Get user's nutritional filter preferences
        $filterInfo = $this->getFilterInfo();

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'titulo' => $this->titulo,
            'descripcion' => $this->descripcion,
            'tiempo' => $this->tiempo,
            'tiempo_nota' => $this->tiempo_nota,
            'tips' => $this->getTips(),
            'instrucciones' => $this->getInstrucciones(),
            'ingredientes' => $this->getIngredientes(),
            'porciones' => $this->getPorciones(),
            'calories' => $this->calories ?? null,
            'nutrient_info' => $this->nutrient_info,
            'nutrientes' => $this->formatNutrientes(),
            'filter_info' => $filterInfo,
            'imagen_principal' => $this->imagen_principal,
            'imagen_secundaria' => $this->imagen_secundaria,
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'comments_count' => $this->comments->count() ?? 0,
            'ingredientes_count' => $this->getCantidadIngredientes(),
            'like_reactions' => $this->like_reactions ?? 0,
            'dislike_reactions' => $this->dislike_reactions ?? 0,
            'reactions' => [
                'likes' => $this->like_reactions ?? 0,
                'dislikes' => $this->dislike_reactions ?? 0,
                'userReaction' => $this->getUserReaction(),
            ],
            'comments' => $this->whenLoaded('comments', function () {
                return $this->comments->map(function ($comment) {
                    return [
                        'id' => $comment->id,
                        'comment' => $comment->comment,
                        'user' => [
                            'id' => $comment->user->id ?? null,
                            'name' => $comment->user->name ?? null,
                            'username' => $comment->user->username ?? null,
                            'image' => $comment->user->image ?? null,
                        ],
                        'created_at' => $comment->created_at,
                        'day' => $comment->created_at ? $comment->created_at->format('d') : '',
                        'month' => $comment->created_at ? $comment->created_at->format('M') : '',
                        'is_owned_by_current_user' => $comment->user_id === auth()->id(),
                    ];
                });
            }),
            'has_subreceta' => $this->has_subreceta,
            'active' => (bool) $this->active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Format nutrient_info into the structure expected by the frontend
     */
    protected function formatNutrientes()
    {
        if (!$this->nutrient_info || !is_array($this->nutrient_info)) {
            return ['info' => []];
        }

        $nutrientes = \App\Models\Nutriente::all()->keyBy('fdc_id');
        $formatted = ['info' => []];

        foreach ($this->nutrient_info as $fdcId => $nutrientData) {
            $nutriente = $nutrientes->get($fdcId);
            
            if (!$nutriente || !($nutrientData['mostrar'] ?? true)) {
                continue;
            }

            $formatted['info'][] = [
                'id' => $nutriente->id,
                'nombre' => $nutriente->nombre,
                'cantidad' => $nutrientData['cantidad'] ?? 0,
                'unidad_medida' => $nutrientData['unidad_medida'] ?? $nutriente->unidad_medida ?? 'g',
                'porcentaje' => $nutrientData['porcentaje'] ?? '-',
                'color' => $nutrientData['color'] ?? '#dcb244',
                'mostrar' => $nutrientData['mostrar'] ?? true,
            ];
        }

        return $formatted;
    }

    /**
     * Get user's nutritional filter preferences
     * Returns array of nutrient IDs that should be displayed
     */
    protected function getFilterInfo()
    {
        if (!auth()->check()) {
            return [];
        }

        $nutritionals = DB::table('nutritional_preferences')
            ->where('user_id', auth()->id())
            ->first();

        if ($nutritionals) {
            $nutritionalsInfo = json_decode($nutritionals->nutritional_info, true);
        } else {
            $nutritionalsInfo = config('constants.nutritients', []);
        }

        $filterInfo = [];
        if (is_array($nutritionalsInfo)) {
            foreach ($nutritionalsInfo as $nutrient) {
                if (isset($nutrient['mostrar']) && $nutrient['mostrar'] == 1) {
                    $filterInfo[] = $nutrient['id'];
                }
            }
        }

        return $filterInfo;
    }
}

