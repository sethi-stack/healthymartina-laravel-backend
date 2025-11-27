<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;

// VALIDATION: change the requests to match your own file names if you need form validation
use App\Http\Requests\UnidadMedidaRequest as StoreRequest;
use App\Http\Requests\UnidadMedidaRequest as UpdateRequest;
use Backpack\CRUD\CrudPanel;

/**
 * Class UnidadMedidaCrudController
 * @package App\Http\Controllers\Admin
 * @property-read CrudPanel $crud
 */
class UnidadMedidaCrudController extends CrudController
{
    public function setup()
    {
        /*
        |--------------------------------------------------------------------------
        | CrudPanel Basic Information
        |--------------------------------------------------------------------------
        */
        $this->crud->setModel('App\Models\UnidadMedida');
        $this->crud->setRoute(config('backpack.base.route_prefix') . '/UnidadesMedida');
        $this->crud->setEntityNameStrings('unidad de medida', 'unidades de medida');

        /*
        |--------------------------------------------------------------------------
        | CrudPanel Configuration
        |--------------------------------------------------------------------------
        */

        // TODO: remove setFromDb() and manually define Fields and Columns
        //$this->crud->setFromDb();
        $this->crud->addColumn([
            'name' => 'nombre',
            'label' => 'Unidad de medida',
        ]);

        $this->crud->addColumn([
            'name' => 'abreviatura',
            'label' => 'Abreviatura',
        ]);

        $this->crud->addColumn([
            'name' => 'equivalencia',
            'label' => 'Equivalencia',
        ]);

        $this->crud->addField([
          'name' => 'nombre',
          'label' => 'Unidad de medida',
          'wrapperAttributes' => [
            'class' => 'form-group col-md-4'
          ],
        ]);

        $this->crud->addField([
          'name' => 'abreviatura',
          'label' => 'Abreviatura',
          'wrapperAttributes' => [
            'class' => 'form-group col-md-4'
          ],
        ]);

        $this->crud->addField([
          'name' => 'equivalencia',
          'label' => 'Equivalencia',
          'wrapperAttributes' => [
            'class' => 'form-group col-md-4'
          ],
        ]);

        // add asterisk for fields that are required in UnidadMedidaRequest
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
