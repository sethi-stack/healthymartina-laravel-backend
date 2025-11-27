<?php

namespace App\Http\Resources\Ingredient;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstruccionResource extends JsonResource
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
            'ingrediente_id' => $this->ingrediente_id,
            'instruccion' => $this->instruccion,
            'recetas_count' => $this->whenCounted('rir'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

