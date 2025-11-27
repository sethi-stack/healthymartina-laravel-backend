<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

// VALIDATION: change the requests to match your own file names if you need form validation
use App\Http\Requests\IngredienteRequest as StoreRequest;
use App\Http\Requests\IngredienteRequest as UpdateRequest;

/**
 * Class IngredienteCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class IngredienteCrudController extends CrudController
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
        CRUD::setModel(\App\Models\Ingrediente::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/Ingredientes');
        CRUD::setEntityNameStrings('ingrediente', 'ingredientes');
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::addColumn([
            'name' => 'id',
            'label' => 'ID',
        ]);

        CRUD::addColumn([
            'name' => 'nombre',
            'label' => 'Nombre',
        ]);

        CRUD::addColumn([
            'name' => 'active',
            'label' => 'Activo',
            'type' => 'boolean',
        ]);

        CRUD::addColumn([
            'name' => 'created_at',
            'label' => 'Creado',
            'type' => 'datetime',
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

        CRUD::addField([
            'name' => 'nombre',
            'label' => 'Nombre',
            'type' => 'text',
        ]);

        CRUD::addField([
            'name' => 'active',
            'label' => 'Activo',
            'type' => 'boolean',
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
}