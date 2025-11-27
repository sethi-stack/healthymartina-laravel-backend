<?php

namespace App\Http\Resources\Ingredient;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IngredientResource extends JsonResource
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
            'nombre' => $this->nombre,
            'usda' => $this->usda,
            'categoria_id' => $this->categoria_id,
            'categoria' => $this->whenLoaded('categoria', function () {
                return [
                    'id' => $this->categoria->id,
                    'nombre' => $this->categoria->nombre,
                ];
            }),
            'forma_compra_id' => $this->forma_compra_id,
            'tipo_medida_id' => $this->tipo_medida_id,
            'instrucciones_count' => $this->whenCounted('instrucciones'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

