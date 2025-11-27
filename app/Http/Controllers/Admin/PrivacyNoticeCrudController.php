<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;

// VALIDATION: change the requests to match your own file names if you need form validation
use App\Http\Requests\PrivacyNoticeRequest as StoreRequest;
use App\Http\Requests\PrivacyNoticeRequest as UpdateRequest;
use Backpack\CRUD\CrudPanel;

/**
 * Class PrivacyNoticeCrudController
 * @package App\Http\Controllers\Admin
 * @property-read CrudPanel $crud
 */
class PrivacyNoticeCrudController extends CrudController
{
    public function setup()
    {
        /*
        |--------------------------------------------------------------------------
        | CrudPanel Basic Information
        |--------------------------------------------------------------------------
        */
        $this->crud->setModel('App\Models\PrivacyNotice');
        $this->crud->setRoute(config('backpack.base.route_prefix') . '/privacy-notice');
        $this->crud->setEntityNameStrings('Aviso de privacidad', 'Avisos de privacidad');
        $this->crud->denyAccess('create');


        /*
        |--------------------------------------------------------------------------
        | CrudPanel Configuration
        |--------------------------------------------------------------------------
        */

        // TODO: remove setFromDb() and manually define Fields and Columns
        // $this->crud->setFromDb();
        $this->crud->addColumn([
            'label' => 'Nombre',
            'name' => 'name',
        ]);

        $this->crud->addColumn([
            'label' => '¿Está activo?',
            'name' => 'active',
            'type' => 'boolean'
        ]);

        $this->crud->addField([
            'label' => "Nombre",
            'name' => 'name',
            'type' => 'text',
        ]);

        $this->crud->addField([
            'label' => "¿Activar?",
            'name' => 'active',
            'type' => 'boolean',
        ]);

        $this->crud->addField([
            'name' => 'content',
            'label' => "Contenido",
            'type' => 'wysiwyg',
        ]);

        // add asterisk for fields that are required in PrivacyNoticeRequest
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
