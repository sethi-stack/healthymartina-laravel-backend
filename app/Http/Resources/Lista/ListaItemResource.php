<?php

namespace App\Http\Resources\Lista;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListaItemResource extends JsonResource
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
            'calendario_id' => $this->calendario_id,
            'cantidad' => $this->cantidad,
            'nombre' => $this->nombre,
            'categoria' => $this->categoria,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

