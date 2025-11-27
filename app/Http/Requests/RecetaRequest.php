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
        return [
            'titulo' => 'required|min:2|max:255',
            'tiempo' => 'nullable|numeric|min:1',
            'porciones' => 'nullable|numeric|min:1',
            'descripcion' => 'nullable|string',
            'instrucciones' => 'nullable|string',
            'active' => 'boolean',
            'editado' => 'boolean',
            'free' => 'boolean',
            'calorias' => 'nullable|numeric|min:0',
            'carbohidratos' => 'nullable|numeric|min:0',
            'proteinas' => 'nullable|numeric|min:0',
            'grasas' => 'nullable|numeric|min:0',
            'imagen_principal' => 'nullable|image|max:2048',
            'imagen_secundaria' => 'nullable|image|max:2048',
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
            'descripcion' => 'descripción',
            'instrucciones' => 'instrucciones',
            'active' => 'activo',
            'editado' => 'editado',
            'free' => 'gratis',
            'calorias' => 'calorías',
            'carbohidratos' => 'carbohidratos',
            'proteinas' => 'proteínas',
            'grasas' => 'grasas',
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
