<?php

use Illuminate\Support\Facades\Route;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

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
    CRUD::resource('Ingredientes', 'IngredienteCrudController');
    CRUD::resource('Recetas', 'RecetaCrudController');
    CRUD::resource('UnidadesMedida', 'UnidadMedidaCrudController');
    CRUD::resource('Clientes', 'ClienteCrudController');
    Route::post('Clientes/registro', 'ClienteCrudController@registro');
    Route::post('Clientes/registro/validarEmail', 'ClienteCrudController@validarEmailRepetido');
    CRUD::resource('membresia', 'MembresiaCrudController');
    CRUD::resource('Categorias', 'CategoriaCrudController');
    CRUD::resource('FormasCompra', 'FormaCompraCrudController');
    CRUD::resource('Instrucciones', 'InstruccionCrudController');
    CRUD::resource('Medidas', 'MedidaCrudController');
    CRUD::resource('Tags', 'TagCrudController');
    CRUD::resource('TiposMedida', 'TipoMedidaCrudController');
    CRUD::resource('RecetaInstruccionReceta', 'RecetaInstruccionRecetaCrudController');
    CRUD::resource('Nutrientes', 'NutrienteCrudController');

    Route::post('Recetas/ingrediente-medida/{ing}', 'RecetaCrudController@ingredienteMedida');
    Route::post('Recetas/receta-medida/{ing}', 'RecetaCrudController@recetaMedida');

    Route::post('Recetas/MedidasPorInstruccion', 'RecetaCrudController@instruccionMedida');

    Route::get('/getFDCData', 'IngredienteCrudController@getFDCData');
    Route::get('/getFDCFood', 'IngredienteCrudController@getFDCFood');

    CRUD::resource('plan', 'PlanCrudController');
    CRUD::resource('nutrienttype', 'NutrientTypeCrudController');
    CRUD::resource('terms-conditions', 'TermsConditionsCrudController');
    CRUD::resource('templates', 'TemplateCrudController');
    CRUD::resource('privacy-notice', 'PrivacyNoticeCrudController');
    CRUD::resource('equivalence', 'EquivalenceCrudController');
    CRUD::resource('comment', 'CommentCrudController');
    CRUD::resource('youtube-channel', 'YoutubeChannelCrudController');
}); // this should be the absolute last line of this file

/**
 * DO NOT ADD ANYTHING HERE.
 */
