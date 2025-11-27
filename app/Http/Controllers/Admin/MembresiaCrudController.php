<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;

// VALIDATION: change the requests to match your own file names if you need form validation
use App\Http\Requests\MembresiaRequest as StoreRequest;
use App\Http\Requests\MembresiaRequest as UpdateRequest;
use Backpack\CRUD\CrudPanel;

/**
 * Class PlanCrudController
 * @package App\Http\Controllers\Admin
 * @property-read CrudPanel $crud
 */
class MembresiaCrudController extends CrudController
{
    public function setup()
    {
        /*
        |--------------------------------------------------------------------------
        | CrudPanel Basic Information
        |--------------------------------------------------------------------------
        */
        $this->crud->setModel('App\Models\Membresia');
        $this->crud->setRoute(config('backpack.base.route_prefix') . '/membresia');
        $this->crud->setEntityNameStrings('membresia', 'membresias');

        /*
        |--------------------------------------------------------------------------
        | CrudPanel Configuration
        |--------------------------------------------------------------------------
        */

        // TODO: remove setFromDb() and manually define Fields and Columns
        // $this->crud->setFromDb();

        $this->crud->addColumn([
            'name' => 'nombre',
            'label' => 'Nombre',
        ]);

        $this->crud->addColumn([
            'name' => 'precio',
            'label' => 'Precio',
            'prefix' => "$",
            'suffix' => '.00'
        ]);

        $this->crud->addField([
            'name' => 'nombre',
            'label' => 'Nombre de la Membresia',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6'
            ],
        ]);

        $this->crud->addField([
            'name' => 'precio',
            'type' => 'number',
            'label' => 'Precio',
            'prefix' => "$",
            'suffix' => ".00",
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6'
            ],
        ]);

        // add asterisk for fields that are required in PlanRequest
        $this->crud->setRequiredFields(StoreRequest::class, 'create');
        $this->crud->setRequiredFields(UpdateRequest::class, 'edit');
    }

    public function store(StoreRequest $request)
    {
        // your additional operations before save here
        $redirect_location = parent::storeCrud($request);
        // your additional operations after save here
        // use $this->data['entry'] or $this->crud->entry
        return $redirect_location;
    }

    public function update(UpdateRequest $request)
    {
        // your additional operations before save here
        $redirect_location = parent::updateCrud($request);
        // your additional operations after save here
        // use $this->data['entry'] or $this->crud->entry
        return $redirect_location;
    }
}
