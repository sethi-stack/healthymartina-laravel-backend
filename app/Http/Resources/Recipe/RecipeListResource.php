<?php

namespace App\Http\Resources\Recipe;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecipeListResource extends JsonResource
{
    /**
     * Transform the resource into an array for listing/search purposes.
     * This is a lightweight version that doesn't load heavy data like
     * ingredients, instructions, tips, etc.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $porciones = $this->getPorciones();

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'titulo' => $this->titulo,
            'descripcion' => $this->descripcion,
            'tiempo' => $this->tiempo,
            'tiempo_nota' => $this->tiempo_nota,
            'calories' => $this->calories ?? null,
            'imagen_principal' => $this->imagen_principal,
            'imagen_secundaria' => $this->imagen_secundaria,
            'imagen_pequena' => $this->imagen_principal,
            'imagen' => $this->imagen_principal,
            'porcion_nombre' => $porciones['nombre'] ?? 'Porción',
            'porcion_nombre_plural' => $porciones['nombre_plural'] ?? 'Porciones',
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'ingredientes_count' => $this->getCantidadIngredientes(),
            'like_reactions' => $this->like_reactions ?? 0,
            'dislike_reactions' => $this->dislike_reactions ?? 0,
            'active' => (bool) $this->active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
