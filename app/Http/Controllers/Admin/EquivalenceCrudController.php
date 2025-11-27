<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;

// VALIDATION: change the requests to match your own file names if you need form validation
use App\Http\Requests\EquivalenceRequest as StoreRequest;
use App\Http\Requests\EquivalenceRequest as UpdateRequest;
use Backpack\CRUD\CrudPanel;

/**
 * Class EquivalenceCrudController
 * @package App\Http\Controllers\Admin
 * @property-read CrudPanel $crud
 */
class EquivalenceCrudController extends CrudController
{
    public function setup()
    {
        /*
        |--------------------------------------------------------------------------
        | CrudPanel Basic Information
        |--------------------------------------------------------------------------
        */
        $this->crud->setModel('App\Models\Equivalence');
        $this->crud->setRoute(config('backpack.base.route_prefix') . '/equivalence');
        $this->crud->setEntityNameStrings('equivalence', 'equivalences');
        $this->crud->denyAccess('create');

        /*
        |--------------------------------------------------------------------------
        | CrudPanel Configuration
        |--------------------------------------------------------------------------
        */

        $this->crud->addColumn([
            'name' => 'content',
            'label' => "Contenido",
            'type' => 'wysiwyg',
        ]);

        // TODO: remove setFromDb() and manually define Fields and Columns
        // $this->crud->setFromDb();
        $this->crud->addField([
            'name' => 'content',
            'label' => "Contenido",
            'type' => 'summernote',
            'options' => [
                ['toolbar', ['bold', 'italic', 'underline', 'fontsize', 'color', 'ul', 'ol', 'paragraph']]
            ]
        ]);

        // add asterisk for fields that are required in EquivalenceRequest
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
