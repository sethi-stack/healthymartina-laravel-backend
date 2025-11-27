<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;

// VALIDATION: change the requests to match your own file names if you need form validation
use App\Http\Requests\MedidaRequest as StoreRequest;
use App\Http\Requests\MedidaRequest as UpdateRequest;
use Backpack\CRUD\CrudPanel;

use App\Models\TipoMedida;
use App\Models\Ingrediente;
use App\Models\Medida;
use App\Models\RecetaResultado;

/**
 * Class MedidaCrudController
 * @package App\Http\Controllers\Admin
 * @property-read CrudPanel $crud
 */
class MedidaCrudController extends CrudController
{
    public function setup()
    {
        /*
        |--------------------------------------------------------------------------
        | CrudPanel Basic Information
        |--------------------------------------------------------------------------
        */
        $this->crud->setModel('App\Models\Medida');
        $this->crud->setRoute(config('backpack.base.route_prefix') . '/Medidas');
        $this->crud->setEntityNameStrings('medida', 'medidas');

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
          'name' => 'nombre_plural',
          'label' => 'Nombre en Plural',
        ]);

        $this->crud->addColumn([
          'name' => 'abreviatura',
          'label' => 'Abreviatura'
        ]);

        $this->crud->addColumn([
          'name' => 'abreviatura_plural',
          'label' => 'Abreviatura en plural'
        ]);

        $this->crud->addColumn([
          'name' => 'nombre_english',
          'label' => 'Nombre en Ingles'
        ]);

        $this->crud->addColumn([
          'name' => 'tipoMedida.nombre',
          'label' => 'Tipo de medida'
        ]);

        // $this->crud->addColumn([
        //   'name' => 'ingrediente.nombre',
        //   'label' => 'Ingrediente'
        // ]);


        $this->crud->addField([
          'name' => 'nombre',
          'label' => 'Nombre',
          // 'wrapperAttributes' => [
          //   'class' => 'form-group col-md-5'
          // ],
        ]);

        $this->crud->addField([
          'name' => 'nombre_plural',
          'label' => 'Nombre en Plural',
          // 'wrapperAttributes' => [
          //   'class' => 'form-group col-md-5'
          // ],
        ]);

        $this->crud->addField([
          'name' => 'abreviatura',
          'label' => 'Abreviatura',
          // 'wrapperAttributes' => [
          //   'class' => 'form-group col-md-2'
          // ],
        ]);

        $this->crud->addField([
          'name' => 'abreviatura_plural',
          'label' => 'Abreviatura en Plural',
          // 'wrapperAttributes' => [
          //   'class' => 'form-group col-md-2'
          // ],
        ]);

        $this->crud->addField([
          'name' => 'nombre_english',
          'label' => 'Nombre en Ingles',
          // 'wrapperAttributes' => [
          //   'class' => 'form-group col-md-2'
          // ],
        ]);

        $this->crud->addField([
          'name' => 'tipo_medida_id',
          'label' => "Tipo de medida",
          'type' => 'select2_from_array',
          'options' => TipoMedida::all()->pluck('nombre', 'id'),
          'allows_null' => false,
          'default' => 'one',
          // 'wrapperAttributes' => [
          //   'class' => 'form-group col-md-5'
          // ],
        ]);

        // $this->crud->addField([
        //   'name' => 'ingrediente_id',
        //   'label' => "Ingrediente",
        //   'type' => 'select2_from_array',
        //   'options' => Ingrediente::all()->pluck('nombre', 'id'),
        //   'allows_null' => false,
        //   'default' => 'one',
        //   'wrapperAttributes' => [
        //     'class' => 'form-group col-md-4'
        //   ],
        // ]);

        if(request()->get('medida')) {
            $this->crud->addClause('where', 'id', request()->get('medida'));
        }

        // add asterisk for fields that are required in MedidaRequest
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
