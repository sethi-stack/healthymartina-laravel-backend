<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;

// VALIDATION: change the requests to match your own file names if you need form validation
use App\Http\Requests\ClienteRequest as StoreRequest;
use App\Http\Requests\ClienteRequest as UpdateRequest;
use Backpack\CRUD\CrudPanel;
use Illuminate\Http\Request;
use App\Models\Cliente;
use App\User;

/**
 * Class ClienteCrudController
 * @package App\Http\Controllers\Admin
 * @property-read CrudPanel $crud
 */
class ClienteCrudController extends CrudController
{
    public function setup()
    {
        /*
        |--------------------------------------------------------------------------
        | CrudPanel Basic Information
        |--------------------------------------------------------------------------
        */
        $this->crud->setModel('App\User');
        
       
        $this->crud->setRoute(config('backpack.base.route_prefix') . '/Clientes');
        $this->crud->setEntityNameStrings('cliente', 'clientes');
        //$this->crud->removeAllButtons();
        $this->crud->denyAccess(['create','update']);

        /*
        |--------------------------------------------------------------------------
        | CrudPanel Configuration
        |--------------------------------------------------------------------------
        */

        // TODO: remove setFromDb() and manually define Fields and Columns
        $this->crud->setFromDb();
        $this->crud->addClause('where', 'is_admin', '=', NULL);
        $this->crud->addColumn([
            'name' => 'created_at',
            'label' => 'Creado en',
            'type'  => 'datetime',
            'format' => 'l',
        ]);
        $this->crud->addColumn([
            'name' => 'name',
            'label' => 'Nombre(s)',
        ]);

        $this->crud->addColumn([
            'name' => 'last_name',
            'label' => 'Apellidos',
        ]);

        $this->crud->addColumn([
            'name' => 'email',
            'label' => 'Email',
        ]);

        $this->crud->addColumn([
            'name' => 'role_id',
            'label' => 'Tipo de Membresia',
            'type' => 'select',
            'entity'     => 'userRole', // the method that defines the relationship in your Model
            'attribute'  => 'name',
            'model'     => App\Role::class,
            'orderable' => true,
        ]);
        
        $this->crud->orderBy('role_id', 'DESC');
        // add asterisk for fields that are required in ClienteRequest
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

    public function registro(Request $request)
    {
        $cliente = new Cliente();
        $cliente->nombre = $request->nombre;
        $cliente->apellidos = $request->apellidos;
        $cliente->email = $request->email;
        $cliente->contrasena = bcrypt($request->contrasena);
        $cliente->save();
    }

    public function validarEmailRepetido(Request $request)
    {
        return User::where('email', $request->email) > 0 ? true : false;
    }

    // public function destroy($id)
    // {
    //     $this->crud->hasAccessOrFail('delete');
    //     $client = User::find($id);
    //     User::whereEmail($client->email)->delete();
    //     return $this->crud->delete($id);
    // }
}
