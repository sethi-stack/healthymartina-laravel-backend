<?php

namespace App\Http\Resources\Recipe;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecipeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
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
            'imagen_principal' => $this->imagen_principal,
            'imagen_secundaria' => $this->imagen_secundaria,
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'comments_count' => $this->comments->count() ?? 0,
            'like_reactions' => $this->like_reactions ?? 0,
            'dislike_reactions' => $this->dislike_reactions ?? 0,
            'has_subreceta' => $this->has_subreceta,
            'active' => (bool) $this->active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

