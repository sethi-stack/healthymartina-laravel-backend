<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $pref = $this->whenLoaded('preference');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'last_name' => $this->last_name,
            'full_name' => $this->name . ' ' . $this->last_name,
            'username' => $this->username,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at,
            // Profile photo
            'image' => $this->image,
            // Business info
            'bname' => $this->bname,
            'profession' => $this->profession,
            'bemail' => $this->bemail,
            'website' => $this->website,
            'bimage' => $this->bimage,
            'color' => $this->color,
            // Preferences
            'unit_measure' => $this->unit_measure,
            'theme' => $this->theme,
            'role_id' => $this->role_id,
            // Notification preferences (loaded relation)
            'preference' => $this->when($this->relationLoaded('preference'), function () {
                $pref = $this->preference;
                if (!$pref) return null;
                return [
                    'weekly_reminders' => (bool) $pref->weekly_reminders,
                    'new_updates' => (bool) $pref->new_updates,
                    'mentions' => (bool) $pref->mentions,
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

