<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;

// VALIDATION: change the requests to match your own file names if you need form validation
use App\Http\Requests\NutrienteRequest as StoreRequest;
use App\Http\Requests\NutrienteRequest as UpdateRequest;
use Backpack\CRUD\CrudPanel;

/**
 * Class NutrienteCrudController
 * @package App\Http\Controllers\Admin
 * @property-read CrudPanel $crud
 */
class NutrienteCrudController extends CrudController
{
    public function setup()
    {
        /*
        |--------------------------------------------------------------------------
        | CrudPanel Basic Information
        |--------------------------------------------------------------------------
        */
        $this->crud->setModel('App\Models\Nutriente');
        $this->crud->setRoute(config('backpack.base.route_prefix') . '/Nutrientes');
        $this->crud->setEntityNameStrings('nutriente', 'nutrientes');

        /*
        |--------------------------------------------------------------------------
        | CrudPanel Configuration
        |--------------------------------------------------------------------------
        */

        // TODO: remove setFromDb() and manually define Fields and Columns
        // $this->crud->setFromDb();

        // $this->crud->addColumn([
        //     'name' => 'orden',
        //     'label' => 'Orden',
        //     'type' => 'text'
        // ]);
        $this->crud->addColumn([
            'name' => 'orden',
            'label' => 'Orden',
            'type' => 'number'
        ]);

        $this->crud->addColumn([
            'name' => 'nombre',
            'label' => 'nombre',
            'type' => 'text'
        ]);

        $this->crud->addColumn([
            'name' => 'nombre_ingles',
            'label' => 'Nombre en inglés',
            'type' => 'text',
        ]);

        $this->crud->addColumn([
            'name' => 'cien_porciento',
            'label' => 'Cantidad que será el 100% del nutriente',
            'type' => 'text',
        ]);

        $this->crud->addColumn([
            'name' => 'mostrar',
            'label' => '¿Mostrar al usuario?',
            'type' => 'boolean',
        ]);

        // $this->crud->addColumn([
        //     'name' => 'decimal',
        //     'label' => '¿Rango de filtro por decimales?',
        //     'type' => 'boolean',
        // ]);

        $this->crud->addColumn([ // select_from_array
            'name' => 'filter',
            'label' => "Filtro",
            'type' => 'select_from_array',
            'options' => ['individual' => 'Individual', 'bussiness' => 'Business', 'both' => 'Ambos'],
        ]);

        $this->crud->addColumn([
            'name' => 'priority',
            'label' => 'Prioridad del Filtro',
            'type' => 'number'
        ]);

        $this->crud->addField([
            'name' => 'orden',
            'label' => 'Orden',
            'type' => 'number'
        ]);

        $this->crud->addField([
            'name' => 'nombre',
            'label' => 'Nombre',
            'type' => 'text'
        ]);

        $this->crud->addField([
            'name' => 'nombre_ingles',
            'label' => 'Nombre en inglés',
            'type' => 'text',
            'attributes' => [
                'readonly' => 'readonly'
            ],
        ]);
        $this->crud->addField([
            'name' => 'cien_porciento',
            'label' => 'Cantidad que será el 100% del nutriente',
            'type' => 'number',
            'attributes' => [
                'step' => '0.01',
            ],
        ]);

        $this->crud->addField([
            'name' => 'nutrient_type_id',
            'label' => 'Tipo de Nutriente',
            'type' => 'select2',
            'entity' => 'nutrientType',
            'attribute' => 'name',
            'model' => 'App\Models\NutrientType',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-4'
            ],
        ]);

        $this->crud->addField([ // select_from_array
            'name' => 'filter',
            'label' => "Filtro",
            'type' => 'select_from_array',
            'options' => ['individual' => 'Individual', 'bussiness' => 'Business', 'both' => 'Ambos'],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-4'
            ],
        ]);

        $this->crud->addField([
            'name' => 'priority',
            'label' => 'Prioridad del Filtro',
            'type' => 'number',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-4'
            ],
        ]);

        $this->crud->addField([
            'name' => 'unidad_medida',
            'label' => 'Unidad Original',
            'type' => 'text',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-4'
            ],
        ]);

        $this->crud->addField([
            'name' => 'factor',
            'label' => 'Factor de conversión',
            'type' => 'number',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-4'
            ],
        ]);

        $this->crud->addField([
            'name' => 'unidad_nueva',
            'label' => 'Unidad para conversión',
            'type' => 'text',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-4'
            ],
        ]);        

        $this->crud->addField([
            'name' => 'mostrar',
            'label' => '¿Mostrar al usuario?',
            'type' => 'boolean',
        ]);

        // add asterisk for fields that are required in NutrienteRequest
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
