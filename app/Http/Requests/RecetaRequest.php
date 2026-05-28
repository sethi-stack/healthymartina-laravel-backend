<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecetaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // only allow updates if the user is logged in
        return backpack_auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $imageUploadOrBase64Rule = function (string $attribute, mixed $value, \Closure $fail): void {
            if ($value === null || $value === '') {
                return;
            }

            // If Backpack sends a real uploaded file, let the built-in validation handle it.
            if ($value instanceof \Illuminate\Http\UploadedFile) {
                $validator = \Validator::make(
                    [$attribute => $value],
                    [$attribute => 'image|max:2048'],
                    [
                        "{$attribute}.image" => 'La imagen debe ser un archivo de imagen válido.',
                        "{$attribute}.max" => 'La imagen no puede ser mayor a 2MB.',
                    ]
                );
                if ($validator->fails()) {
                    $fail($validator->errors()->first($attribute));
                }
                return;
            }

            if (!is_string($value)) {
                $fail("El campo {$attribute} debe ser una imagen válida.");
                return;
            }

            // When editing, Backpack may send the existing stored path as a string.
            // In that case we should accept it as-is (no new upload to validate).
            if (!\Illuminate\Support\Str::startsWith($value, 'data:image')) {
                return;
            }

            if (!preg_match('#^data:image/(png|jpe?g|webp|gif);base64,#i', $value)) {
                $fail("El campo {$attribute} debe ser una imagen válida.");
                return;
            }

            $base64 = substr($value, strpos($value, ',') + 1);
            $decoded = base64_decode($base64, true);
            if ($decoded === false) {
                $fail("El campo {$attribute} debe ser una imagen válida.");
                return;
            }

            // 2MB limit (same as max:2048 for UploadedFile)
            if (strlen($decoded) > 2 * 1024 * 1024) {
                $fail("El campo {$attribute} no puede ser mayor a 2MB.");
            }
        };

        return [
            'titulo' => 'required|min:2|max:255',
            'tiempo' => 'nullable|numeric|min:1',
            'porciones' => 'nullable|numeric|min:1',
            'instrucciones' => 'nullable|string',
            'active' => 'boolean',
            'editado' => 'boolean',
            'free' => 'boolean',
            // Backpack's `image` field may submit either an UploadedFile or a base64 data URL.
            'imagen_principal' => ['nullable', $imageUploadOrBase64Rule],
            'imagen_secundaria' => ['nullable', $imageUploadOrBase64Rule],
        ];
    }

    /**
     * Get the validation attributes that apply to the request.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'titulo' => 'título de la receta',
            'tiempo' => 'tiempo de preparación',
            'porciones' => 'número de porciones',
            'instrucciones' => 'instrucciones',
            'active' => 'activo',
            'editado' => 'editado',
            'free' => 'gratis',
            'imagen_principal' => 'imagen principal',
            'imagen_secundaria' => 'imagen secundaria',
        ];
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'titulo.required' => 'El título de la receta es obligatorio.',
            'titulo.min' => 'El título debe tener al menos 2 caracteres.',
            'titulo.max' => 'El título no puede tener más de 255 caracteres.',
            'tiempo.numeric' => 'El tiempo debe ser un número.',
            'tiempo.min' => 'El tiempo debe ser mayor a 0.',
            'porciones.numeric' => 'Las porciones deben ser un número.',
            'porciones.min' => 'Las porciones deben ser mayor a 0.',
            'imagen_principal.image' => 'La imagen principal debe ser un archivo de imagen válido.',
            'imagen_principal.max' => 'La imagen principal no puede ser mayor a 2MB.',
            'imagen_secundaria.image' => 'La imagen secundaria debe ser un archivo de imagen válido.',
            'imagen_secundaria.max' => 'La imagen secundaria no puede ser mayor a 2MB.',
        ];
    }
}
