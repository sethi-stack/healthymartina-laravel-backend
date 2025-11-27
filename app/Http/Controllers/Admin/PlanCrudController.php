<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;

// VALIDATION: change the requests to match your own file names if you need form validation
use App\Http\Requests\PlanRequest as StoreRequest;
use App\Http\Requests\PlanRequest as UpdateRequest;
use App\Models\NewReceta as Receta;
use App\Models\PlanReceta;
use Backpack\CRUD\CrudPanel;

/**
 * Class PlanCrudController
 * @package App\Http\Controllers\Admin
 * @property-read CrudPanel $crud
 */
class PlanCrudController extends CrudController
{
    public function setup()
    {
        /*
        |--------------------------------------------------------------------------
        | CrudPanel Basic Information
        |--------------------------------------------------------------------------
        */
        $this->crud->setModel('App\Models\Plan');
        $this->crud->setRoute(config('backpack.base.route_prefix') . '/plan');
        $this->crud->setEntityNameStrings('plan', 'planes');

        /*
        |--------------------------------------------------------------------------
        | CrudPanel Configuration
        |--------------------------------------------------------------------------
        */

        // TODO: remove setFromDb() and manually define Fields and Columns
        // $this->crud->setFromDb();

        /////////////////////Fields

        $this->crud->addField([
            'name' => 'nombre',
            'label' => 'Nombre',
            'type' => 'text',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-4'
            ],
            'attributes' => [
                'required' => true,
            ],
        ]);

        $this->crud->addField([
            'name' => 'guia',
            'label' => 'Guia',
            'type' => 'upload',
            'upload' => true,
            'wrapperAttributes' => [
                'class' => 'form-group col-md-4 upload-pdf-guia'
            ],
            // 'attributes' => [
            //     'required' => true,
            // ],
        ]);

        $this->crud->addField([
            'name' => 'duracion',
            'label' => 'Duración',
            'type' => 'number',
            'suffix' => "días",
            'wrapperAttributes' => [
                'class' => 'form-group col-md-4'
            ],
            'attributes' => [
                'required' => true,
            ],
            
        ]);

        $this->crud->addField([
            'name' => 'editado',
            'label' => 'Editado',
            'type' => 'checkbox',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-2'
            ],
            
        ]);

        $this->crud->addField([
            'name' => 'manual',
            'label' => 'Manual',
            'type' => 'checkbox',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-2'
            ],
            
        ]);


        $this->crud->addField([
           // 1-n relationship
           'label' => "Tipo", // Table column heading
           'type' => "select2",
           'name' => 'tipo_id', // the column that contains the ID of that connected entity;
           'entity' => 'tipo', // the method that defines the relationship in your Model
           'attribute' => "nombre", // foreign key attribute that is shown to user
           'model' => "App\Models\Tipo", // foreign key model
           'wrapperAttributes' => [
                'class' => 'form-group col-md-4'
           ],
           'attributes' => [
                'required' => true,
            ],
        ]);

        $this->crud->addField([
            'name' => 'introduccion',
            'label' => "Introducción",
            'type' => 'textarea',
            'attributes' => [
                'required' => true,
            ],
        ]);

        $this->crud->addField([
            'name' => 'descripcion',
            'label' => "Descripción",
            'type' => 'tinymce',
            'attributes' => [
                'required' => true,
            ],
        ]);

        // $this->crud->addField([
        //     'name' => 'icono',
        //     'label' => 'Icono',
        //     'type' => 'image',
        //     'upload' => true,
        //     'disk' => 'public',
        // ]);

        $this->crud->addField([
            'name' => 'svg',
            'label' => 'Icono',
            'type' => 'textarea',
        ]);

        $this->crud->addField([
            'label' => "Recetas",
            'type' => 'select2_multiple',
            'name' => 'recetas', // the method that defines the relationship in your Model
            'entity' => 'recetas', // the method that defines the relationship in your Model
            'attribute' => 'titulo', // foreign key attribute that is shown to user
            'model' => "App\Models\Receta", // foreign key model
            'pivot' => true, // on create&update, do you need to add/delete pivot table entries?
            // 'select_all' => true, // show Select All and Clear buttons?
            'wrapperAttributes' => [
                'class' => 'form-group col-xs-12 plan_mutiple_receta'
           ],
        ]);

        $this->crud->addField([   // Table
            'name' => 'plan_receta',
            'label' => 'Calendario',
            'type' => 'calendario_planes',
            'attributes' => [
            'class' => 'form-control sitem-search-input',
            ],
            "value">'',
            'models' => 'App\Models\PlanReceta',
            'columns' => [
                'recetas' => [
                    'label' => 'Receta',
                    'type' => 'select2_from_array',
                    'options' =>  Receta::all()->pluck('titulo', 'id')->toArray(),
                ],
                'days' => [
                        'day_1' => 'Lunes',
                        'day_2' => 'Martes',
                        'day_3' => 'Miércoles',
                        'day_4' => 'Jueves',
                        'day_5' => 'Viernes',
                        'day_6' => 'Sábado',
                        'day_7' => 'Domingo',
                ],
                'meals' =>[
                        'meal_1' => 'Desayuno',
                        'meal_2' => 'Lunch',
                        'meal_3' => 'Comida',
                        'meal_4' => 'Snack',
                        'meal_5' => 'Cena',
                        'meal_6' => 'Otros',
                ],
               // 'name' => 'test'
            ],
            'max' => 7, // maximum rows allowed in the table
            'min' => 0, // minimum rows allowed in the table
        ]);
        
        /////////////////////////Columns

        $this->crud->addColumn([
            'name' => 'nombre',
            'label' => 'Nombre'
        ]);

        $this->crud->addColumn([
            'name' => 'guia',
            'label' => 'Guia',
            'type' => 'a'
        ]);

        $this->crud->addColumn([
            'name' => 'editado',
            'label' => 'Editado',
            'type' => 'check'
        ]);

        //manual column
        $this->crud->addColumn([
            'name' => 'manual',
            'label' => 'Manual',
            'type' => 'check'
        ]);
        
        $this->crud->addColumn([
           // 1-n relationship
           'label' => "Tipo", // Table column heading
           'type' => "select",
           'name' => 'tipo_id', // the column that contains the ID of that connected entity;
           'entity' => 'tipo', // the method that defines the relationship in your Model
           'attribute' => "nombre", // foreign key attribute that is shown to user
           'model' => "App\Models\Tipo", // foreign key model
        ]);

        $this->crud->addColumn([
            'name' => 'svg',
            'label' => 'Icono',
            'type' => 'svg',
        ]);



        // add asterisk for fields that are required in PlanRequest
        $this->crud->setRequiredFields(StoreRequest::class, 'create');
        $this->crud->setRequiredFields(UpdateRequest::class, 'edit');
    }

    public function store(StoreRequest $request)
    {
        
        // your additional operations before save here
       
        $Cmain = [];
        $Mservings = [];
        $Mleftover = [];
        $Sservings = [];
        $Sleftover = [];
        $Cside = [];
       
       // dd($Cside,$Cmain );
        $redirect_location = parent::storeCrud($request);
        foreach ($request->plan_receta as $mealkey => $value) {
            foreach ($value as $daykey => $value1) {
                $Cmain[$daykey][$mealkey]= $value1['main']['id'];
                $Mservings[$daykey][$mealkey] = $value1['main']['porcion'];  
                $Mleftover[$daykey][$mealkey] = $value1['main']['leftover'];  
                $Cside[$daykey][$mealkey] = $value1['side']['id'];
                $Sservings[$daykey][$mealkey] = $value1['side']['porcion'];
                $Sleftover[$daykey][$mealkey] = $value1['side']['leftover'];    
            }
        }

        $plan_receta = new PlanReceta();
        $plan_receta->main_schedule = json_encode($Cmain);
        $plan_receta->main_leftovers = json_encode($Mleftover);
        $plan_receta->main_servings = json_encode($Mservings);
        $plan_receta->sides_schedule = json_encode($Cside);
        $plan_receta->sides_leftovers = json_encode($Sleftover);
        $plan_receta->sides_servings = json_encode($Sservings);
        $plan_receta->plan_id = $this->crud->entry->id;
        //$cLabels = array_merge($request->days,$request->meals);
        $days = [
            "days" => $request->days,
        ];
        $meals = [
            "meals" => $request->meals,
        ];
        $cLabels = array_merge($days,$meals);
        $plan_receta->labels = json_encode($cLabels);
        $plan_receta->save();
        // your additional operations after save here
        // use $this->data['entry'] or $this->crud->entry
        return $redirect_location;
    }

    public function update(UpdateRequest $request)
    {

        $Cmain = [];
        $Mservings = [];
        $Mleftover = [];
        $Sservings = [];
        $Sleftover = [];
        $Cside = [];
        $redirect_location = parent::updateCrud($request);
         foreach ($request->plan_receta as $mealkey => $value) {
            foreach ($value as $daykey => $value1) {
                $Cmain[$daykey][$mealkey]= $value1['main']['id'];  
                $Mservings[$daykey][$mealkey] = $value1['main']['porcion'];  
                $Mleftover[$daykey][$mealkey] = $value1['main']['leftover'];  
                $Cside[$daykey][$mealkey] = $value1['side']['id'];
                $Sservings[$daykey][$mealkey] = $value1['side']['porcion'];
                $Sleftover[$daykey][$mealkey] = $value1['side']['leftover'];    
            }
        }
        $plan_receta = PlanReceta::where('plan_id',$this->request->request->get('id'))->first();
        if ($plan_receta === null) {
          $plan_receta = new PlanReceta();
          $plan_receta->plan_id = $this->request->request->get('id');
        }
        $plan_receta->main_schedule = json_encode($Cmain);
      
        $plan_receta->main_leftovers = json_encode($Mleftover);
        $plan_receta->main_servings = json_encode($Mservings);
   

        $plan_receta->sides_schedule = json_encode($Cside);
        $plan_receta->sides_leftovers = json_encode($Sleftover);
        $plan_receta->sides_servings = json_encode($Sservings);
        $days = [
            "days" => $request->days,
        ];
        $meals = [
            "meals" => $request->meals,
        ];
        $cLabels = array_merge($days,$meals);
        $plan_receta->labels = json_encode($cLabels);
        //dd('paln', $plan_receta);
        try{
         $plan_receta->save();
        }
        catch(\Exception $e){
        // do task when error
        echo $e->getMessage();   // insert query
        }
        // your additional operations after save here
        // use $this->data['entry'] or $this->crud->entry
        return $redirect_location;
    }
}
