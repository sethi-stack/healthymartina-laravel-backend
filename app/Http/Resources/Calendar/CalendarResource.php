<?php

namespace App\Http\Resources\Calendar;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CalendarResource extends JsonResource
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
            'user_id' => $this->user_id,
            'nombre' => $this->nombre,
            'semanas' => $this->semanas,
            'estado' => $this->estado,
            'calendario' => $this->calendario ? json_decode($this->calendario, true) : [],
            'data_semanal' => $this->data_semanal ? json_decode($this->data_semanal, true) : [],
            'nombre_pdf' => $this->nombre_pdf,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

