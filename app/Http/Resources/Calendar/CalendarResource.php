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
            'title' => $this->nombre, // Alias for compatibility
            'semanas' => $this->semanas,
            'estado' => $this->estado,
            'calendario' => $this->calendario ? json_decode($this->calendario, true) : [],
            'data_semanal' => $this->data_semanal ? json_decode($this->data_semanal, true) : [],
            'nombre_pdf' => $this->nombre_pdf,
            // Schedule fields
            'labels' => $this->labels ? json_decode($this->labels, true) : null,
            'main_schedule' => $this->main_schedule ? json_decode($this->main_schedule, true) : null,
            'sides_schedule' => $this->sides_schedule ? json_decode($this->sides_schedule, true) : null,
            'main_servings' => $this->main_servings ? json_decode($this->main_servings, true) : null,
            'sides_servings' => $this->sides_servings ? json_decode($this->sides_servings, true) : null,
            'main_leftovers' => $this->main_leftovers ? json_decode($this->main_leftovers, true) : null,
            'sides_leftovers' => $this->sides_leftovers ? json_decode($this->sides_leftovers, true) : null,
            'main_racion' => $this->main_racion ? json_decode($this->main_racion, true) : null,
            'sides_racion' => $this->sides_racion ? json_decode($this->sides_racion, true) : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

