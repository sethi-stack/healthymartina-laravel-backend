<?php

namespace App\Http\Resources\Subscription;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MembresiaResource extends JsonResource
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
            'descripcion' => $this->descripcion,
            'precio' => $this->precio,
            'product' => $this->product,
            'activa' => (bool) $this->activa,
            'orden' => $this->orden,
            'detalles' => $this->whenLoaded('detalles', function () {
                return $this->detalles->map(function ($detalle) {
                    return [
                        'id' => $detalle->id,
                        'detalle' => $detalle->detalle,
                        'valor' => $detalle->valor,
                    ];
                });
            }),
            'role' => $this->whenLoaded('role', function () {
                return [
                    'id' => $this->role->id,
                    'name' => $this->role->name,
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

