<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Ingrediente;

class IngredienteController extends Controller
{
    public function index(Request $request)
    {
        $search_term = $request->input('q');
        $page = $request->input('page');

        if ($search_term) {
            $results = Ingrediente::where('nombre', 'LIKE', '%' . $search_term . '%')->paginate(10);
        } else {
            $results = Ingrediente::paginate(10);
        }

        return $results;
    }

    public function show($id)
    {
        return Ingrediente::find($id);
    }
    public function instruccion($id)
    {
        $ingrediente = Ingrediente::find($id);
        return $ingrediente->instrucciones;
    }
}
