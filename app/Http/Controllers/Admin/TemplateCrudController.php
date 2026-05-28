<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

// VALIDATION: change the requests to match your own file names if you need form validation
use App\Http\Requests\TemplateRequest as StoreRequest;
use App\Http\Requests\TemplateRequest as UpdateRequest;

/**
 * Class TemplateCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class TemplateCrudController extends CrudController
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
        CRUD::setModel(\App\Models\Template::class);
        CRUD::setRoute(config("backpack.base.route_prefix") . "/templates");
        CRUD::setEntityNameStrings("plantilla", "plantillas");
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        // Legacy-aligned list: keep it minimal & readable.
        $this->crud->setColumns([
            [
                'name' => 'name',
                'type' => 'text',
                'label' => 'Nombre',
            ],
            [
                'name' => 'active',
                'type' => 'boolean',
                'label' => '¿Está activo?',
            ],
            [
                'name' => 'filter',
                'type' => 'text',
                'label' => 'Filtro',
                'limit' => 80,
            ],
            [
                'name' => 'updated_at',
                'type' => 'datetime',
                'label' => 'Actualizado',
            ],
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

        // Legacy behavior: HTML editor for template body.
        CRUD::setFromDb(); // set fields from db columns.
        $this->crud->removeAllFields();
        $this->crud->addFields([
            [
                'name' => 'name',
                'type' => 'text',
                'label' => 'Nombre',
            ],
            [
                'name' => 'active',
                'type' => 'boolean',
                'label' => '¿Está activo?',
            ],
            [
                'name' => 'filter',
                'type' => 'text',
                'label' => 'Filtro',
            ],
            [
                'name' => 'content',
                'type' => 'summernote',
                'label' => 'Contenido',
                'options' => [
                    'height' => 350,
                ],
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
}
