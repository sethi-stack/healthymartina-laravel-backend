<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;

// VALIDATION: change the requests to match your own file names if you need form validation
use App\Http\Requests\InstruccionRequest as StoreRequest;
use App\Http\Requests\InstruccionRequest as UpdateRequest;
use Backpack\CRUD\CrudPanel;

/**
 * Class InstruccionCrudController
 * @package App\Http\Controllers\Admin
 * @property-read CrudPanel $crud
 */
class InstruccionCrudController extends CrudController
{
    public function setup()
    {
        /*
        |--------------------------------------------------------------------------
        | CrudPanel Basic Information
        |--------------------------------------------------------------------------
        */
        $this->crud->setModel('App\Models\Instruccion');
        $this->crud->setRoute(config('backpack.base.route_prefix') . '/Instrucciones');
        $this->crud->setEntityNameStrings('instrucción', 'instrucciones');
        $this->crud->setCreateView('vendor/backpack/crud/create-instruccion');
        //$this->crud->setListView('vendor/backpack/crud/list_peticion_gasto');


        /*
        |--------------------------------------------------------------------------
        | CrudPanel Configuration
        |--------------------------------------------------------------------------
        */

        // TODO: remove setFromDb() and manually define Fields and Columns
        //$this->crud->setFromDb();
        $this->crud->addColumn([
          'name' => 'nombre',
          'label' => 'Nombre'
        ]);

        $this->crud->addColumn([
          'name' => 'equivalente',
          'label' => 'Equivalente'
        ]);

        $this->crud->addColumn([
            'label' => "Medida", // Table column heading
            'type' => "select",
            'name' => 'equivalencia_medida_id', // the column that contains the ID of that connected entity;
            'entity' => 'equivalenciaMedida', // the method that defines the relationship in your Model
            'attribute' => "nombre", // foreign key attribute that is shown to user
            'model' => "App\Models\Medida",
        ]);

        $this->crud->addColumn([
          'name' => 'ingrediente.nombre',
          'label' => 'Ingrediente'
        ]);

        $this->crud->addColumn([
          'name' => 'cantidad_tipo_medida',
          'label' => 'Cantidad'
        ]);

        $this->crud->addColumn([
          'name' => 'medida.nombre',
          'label' => 'Medida'
        ]);

        $this->crud->addField([
            'name' => 'nombre',
            'label' => 'Nombre',
            'type' => 'text',
        ]);

        $this->crud->addField([
            'name' => 'equivalente',
            'label' => 'Equivalente',
            'type' => 'text',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6'
            ],
        ]);

        $this->crud->addField([
            'name' => 'equivalencia_medida_id',
            'label' => 'Medida',
            'type' => 'select2',
            'entity' => 'medida',
            'attribute' => 'nombre',
            'model' => 'App\Models\Medida',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6'
            ],
            // 'options'   => (function ($query) {
            //     return $query->whereHas('tipoMedida', function($q){
            //       $q->where('id', 1);//VOLUMEN
            //     })->get();
            // }),
        ]);

        $this->crud->addField([
            'name' => 'cantidad_tipo_medida',
            'label' => 'Cantidad',
            'type' => 'text',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6'
            ],
        ]);

        $this->crud->addField([
            'name' => 'medida_id',
            'label' => 'Medida',
            'type' => 'select2',
            'entity' => 'medida',
            'attribute' => 'nombre',
            'model' => 'App\Models\Medida',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6'
            ],
            // 'options'   => (function ($query) {
            //     return $query->whereHas('tipoMedida', function($q){
            //       $q->where('id', 2);//PESO
            //     })->get();
            // }),
        ]);

        $this->crud->addField([
        'label' => "Ingrediente",
         'type' => 'select2',
         'name' => 'ingrediente_id', // the db column for the foreign key
         'entity' => 'ingrediente', // the method that defines the relationship in your Model
         'attribute' => 'nombre', // foreign key attribute that is shown to user
         'model' => "App\Models\Ingrediente", // foreign key model
         'default' => request()->get('ingrediente') ? request()->get('ingrediente') : null,
        ]);

        if(request()->get('ingrediente')) {
            $this->crud->addClause('where', 'ingrediente_id', request()->get('ingrediente'));
            $this->crud->removeButton('create');
            $this->crud->removeButton('update');
            $this->crud->addButtonFromModelFunction('top', 'crear', 'anadirButton', 'end');
            $this->crud->addButtonFromModelFunction('line', 'editar', 'editarButton', 'beginning');
        }

        // add asterisk for fields that are required in InstruccionRequest
        $this->crud->setRequiredFields(StoreRequest::class, 'create');
        $this->crud->setRequiredFields(UpdateRequest::class, 'edit');
    }

    public function store(StoreRequest $request)
    {
        if(strpos($request->request->get('http_referrer'), 'ingrediente') !== false){
          $ingrediente = explode('ingrediente=', $request->request->get('http_referrer'))[1];
          $request->request->set('ingrediente_id', $ingrediente);
        }

        //dd($request->request);
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
