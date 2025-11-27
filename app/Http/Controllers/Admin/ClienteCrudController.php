<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

// VALIDATION: change the requests to match your own file names if you need form validation
use App\Http\Requests\ClienteRequest as StoreRequest;
use App\Http\Requests\ClienteRequest as UpdateRequest;
use Illuminate\Http\Request;
use App\Models\Cliente;
use App\Models\User;
use App\Models\Role;

/**
 * Class ClienteCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class ClienteCrudController extends CrudController
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
        CRUD::setModel(\App\Models\User::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/Clientes');
        CRUD::setEntityNameStrings('cliente', 'clientes');
        
        // Deny create and update operations
        CRUD::denyAccess(['create', 'update']);
        
        // Only show non-admin users
        CRUD::addClause('whereNull', 'is_admin');
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
            'name' => 'created_at',
            'label' => 'Creado en',
            'type' => 'datetime',
        ]);

        CRUD::addColumn([
            'name' => 'name',
            'label' => 'Nombre(s)',
        ]);

        CRUD::addColumn([
            'name' => 'last_name',
            'label' => 'Apellidos',
        ]);

        CRUD::addColumn([
            'name' => 'email',
            'label' => 'Email',
        ]);

        CRUD::addColumn([
            'name' => 'role_id',
            'label' => 'Tipo de Membresía',
            'type' => 'select',
            'entity' => 'userRole',
            'attribute' => 'name',
            'model' => Role::class,
        ]);
        
        CRUD::orderBy('role_id', 'DESC');
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
        
        // Add fields here if create operation is enabled
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
     * Custom registration method
     */
    public function registro(Request $request)
    {
        $cliente = new Cliente();
        $cliente->nombre = $request->nombre;
        $cliente->apellidos = $request->apellidos;
        $cliente->email = $request->email;
        $cliente->contrasena = bcrypt($request->contrasena);
        $cliente->save();
    }

    /**
     * Validate if email is already taken
     */
    public function validarEmailRepetido(Request $request)
    {
        return User::where('email', $request->email)->count() > 0;
    }
}