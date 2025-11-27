<?php

use Illuminate\Support\Facades\Route;

// --------------------------
// Custom Backpack Routes
// --------------------------
// This route file is loaded automatically by Backpack\CRUD.
// Routes you generate using Backpack\Generators will be placed here.

Route::group([
    'prefix' => config('backpack.base.route_prefix', 'admin'),
    'middleware' => array_merge(
        (array) config('backpack.base.web_middleware', 'web'),
        (array) config('backpack.base.middleware_key', 'admin')
    ),
    'namespace' => 'App\Http\Controllers\Admin',
], function () { // custom admin routes
    Route::crud('Ingredientes', 'IngredienteCrudController');
    Route::crud('Recetas', 'RecetaCrudController');
    Route::crud('UnidadesMedida', 'UnidadMedidaCrudController');
    Route::crud('Clientes', 'ClienteCrudController');
    Route::post('Clientes/registro', 'ClienteCrudController@registro');
    Route::post('Clientes/registro/validarEmail', 'ClienteCrudController@validarEmailRepetido');
    Route::crud('membresia', 'MembresiaCrudController');
    Route::crud('Categorias', 'CategoriaCrudController');
    Route::crud('FormasCompra', 'FormaCompraCrudController');
    Route::crud('Instrucciones', 'InstruccionCrudController');
    Route::crud('Medidas', 'MedidaCrudController');
    Route::crud('Tags', 'TagCrudController');
    Route::crud('TiposMedida', 'TipoMedidaCrudController');
    Route::crud('RecetaInstruccionReceta', 'RecetaInstruccionRecetaCrudController');
    Route::crud('Nutrientes', 'NutrienteCrudController');

    Route::post('Recetas/ingrediente-medida/{ing}', 'RecetaCrudController@ingredienteMedida');
    Route::post('Recetas/receta-medida/{ing}', 'RecetaCrudController@recetaMedida');

    Route::post('Recetas/MedidasPorInstruccion', 'RecetaCrudController@instruccionMedida');

    Route::get('/getFDCData', 'IngredienteCrudController@getFDCData');
    Route::get('/getFDCFood', 'IngredienteCrudController@getFDCFood');

    Route::crud('plan', 'PlanCrudController');
    Route::crud('nutrienttype', 'NutrientTypeCrudController');
    Route::crud('terms-conditions', 'TermsConditionsCrudController');
    Route::crud('templates', 'TemplateCrudController');
    Route::crud('privacy-notice', 'PrivacyNoticeCrudController');
    Route::crud('equivalence', 'EquivalenceCrudController');
    Route::crud('comment', 'CommentCrudController');
    Route::crud('youtube-channel', 'YoutubeChannelCrudController');
}); // this should be the absolute last line of this file

/**
 * DO NOT ADD ANYTHING HERE.
 */
