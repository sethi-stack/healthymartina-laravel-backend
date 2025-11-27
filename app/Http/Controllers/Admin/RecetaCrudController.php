<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

// VALIDATION: change the requests to match your own file names if you need form validation
use App\Http\Requests\RecetaRequest as StoreRequest;
use App\Http\Requests\RecetaRequest as UpdateRequest;
use App\Models\NewReceta as Receta;
use App\Models\Ingrediente;
use App\Models\Instruccion;
use App\Models\Medida;
use Illuminate\Http\Request;

/**
 * Class RecetaCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class RecetaCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     * 
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\NewReceta::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/Recetas');
        CRUD::setEntityNameStrings('receta', 'recetas');
        
        // Enable details row for complex data
        CRUD::enableDetailsRow();
        CRUD::allowAccess('details_row');
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        // Add filters
        CRUD::addFilter([
            'name'  => 'active',
            'type'  => 'select2',
            'label' => 'Activo'
        ], function () {
            return [
            0 => 'No',
            1 => 'Si',
            ];
        }, function ($value) {
            CRUD::addClause('where', 'active', $value);
        });

        CRUD::addFilter([
            'name'  => 'editado',
            'type'  => 'select2',
            'label' => 'Editado'
        ], function () {
            return [
            0 => 'No',
            1 => 'Si',
            ];
        }, function ($value) {
            CRUD::addClause('where', 'editado', $value);
        });

        // Add columns
        CRUD::addColumn([
            'name' => 'id',
            'label' => 'ID',
        ]);

        CRUD::addColumn([
            'name' => 'titulo',
            'label' => 'Título',
        ]);

        CRUD::addColumn([
            'name' => 'like_reactions',
            'label' => 'Me gusta',
        ]);

        CRUD::addColumn([
            'name' => 'dislike_reactions',
            'label' => 'No me gusta',
        ]);

        CRUD::addColumn([
            'name' => 'tiempo',
            'label' => 'Tiempo',
            'suffix' => ' minutos'
        ]);

        CRUD::addColumn([
            'name' => 'porciones',
            'label' => 'Porciones',
        ]);

        CRUD::addColumn([
            'name' => 'editado',
            'label' => 'Editado',
            'type' => 'boolean',
        ]);

        CRUD::addColumn([
            'name' => 'active',
            'label' => 'Activo',
            'type' => 'boolean',
        ]);

        CRUD::addColumn([
            'name' => 'free',
            'label' => 'Gratis',
            'type' => 'boolean',
        ]);

        CRUD::addColumn([
            'name' => 'imagen_principal',
            'label' => 'Imagen Principal',
            'type' => 'image'
        ]);
    }

    /**
     * Define what happens when the Create operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(StoreRequest::class);

        // Basic fields
        CRUD::addField([
            'name' => 'titulo',
            'label' => 'Título',
            'type' => 'text',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6'
            ],
        ]);

        CRUD::addField([
            'name' => 'tiempo',
            'label' => 'Tiempo (minutos)',
            'type' => 'number',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-3'
            ],
        ]);

        CRUD::addField([
            'name' => 'porciones',
            'label' => 'Porciones',
            'type' => 'number',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-3'
            ],
        ]);

        CRUD::addField([
            'name' => 'descripcion',
            'label' => 'Descripción',
            'type' => 'textarea',
        ]);

        CRUD::addField([
            'name' => 'instrucciones',
            'label' => 'Instrucciones',
            'type' => 'textarea',
        ]);

        // Checkboxes
        CRUD::addField([
            'name' => 'active',
            'label' => 'Activo',
            'type' => 'checkbox',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-4'
            ],
        ]);
       
        CRUD::addField([
            'name' => 'editado',
            'label' => 'Editado',
            'type' => 'checkbox',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-4'
            ],
        ]);

        CRUD::addField([
            'name' => 'free',
            'label' => 'Gratis',
            'type' => 'checkbox',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-4'
            ],
        ]);
        
        // Images
        CRUD::addField([
            'name' => 'imagen_principal',
            'label' => 'Imagen Principal',
            'type' => 'upload',
            'upload' => true,
            'disk' => 'public',
        ]);

        CRUD::addField([
            'name' => 'imagen_secundaria',
            'label' => 'Imagen Secundaria',
            'type' => 'upload',
            'upload' => true,
            'disk' => 'public',
        ]);

        // Nutritional info
        CRUD::addField([
            'name' => 'calorias',
            'label' => 'Calorías',
            'type' => 'number',
            'attributes' => ['step' => '0.01'],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-3'
            ],
        ]);

        CRUD::addField([
            'name' => 'carbohidratos',
            'label' => 'Carbohidratos (g)',
            'type' => 'number',
            'attributes' => ['step' => '0.01'],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-3'
            ],
        ]);

        CRUD::addField([
            'name' => 'proteinas',
            'label' => 'Proteínas (g)',
            'type' => 'number',
            'attributes' => ['step' => '0.01'],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-3'
            ],
        ]);

        CRUD::addField([
            'name' => 'grasas',
            'label' => 'Grasas (g)',
            'type' => 'number',
            'attributes' => ['step' => '0.01'],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-3'
            ],
        ]);
    }

    /**
     * Define what happens when the Update operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    /**
     * Show the details for a recipe
     */
    public function showDetailsRow($id)
    {
        $recipe = $this->crud->model->findOrFail($id);
        
        return view('admin.recipe_details', compact('recipe'));
    }

    /**
     * Custom methods for ingredient and instruction management
     */
    public function ingredienteMedida($ing)
    {
        $ingrediente = Ingrediente::find($ing);
        return response()->json($ingrediente->medidas);
    }

    public function recetaMedida($ing)
    {
        $receta = Receta::find($ing);
        return response()->json($receta->medidas ?? []);
    }

    public function instruccionMedida(Request $request)
    {
        $instruccion = Instruccion::find($request->instruccion_id);
        return response()->json($instruccion->medidas ?? []);
    }
}