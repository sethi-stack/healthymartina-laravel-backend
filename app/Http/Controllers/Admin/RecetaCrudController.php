<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;

// VALIDATION: change the requests to match your own file names if you need form validation
use App\Http\Requests\RecetaRequest as StoreRequest;
use App\Http\Requests\RecetaRequest as UpdateRequest;
use Backpack\CRUD\CrudPanel;
use App\Models\NewReceta as Receta;
use App\Models\Ingrediente;
use App\Models\Instruccion;
use App\Models\Medida;
use App\Models\IngredienteReceta;
use App\Models\RecetaInstruccionRecetaMedida;
use App\Models\RecetaInstruccionReceta;
use App\Models\RecetaResultado;
use Exception;
use Illuminate\Http\Request;

/**
 * Class RecetaCrudController
 * @package App\Http\Controllers\Admin
 * @property-read CrudPanel $crud
 */
class RecetaCrudController extends CrudController
{
    public function setup()
    {
        /*
        |--------------------------------------------------------------------------
        | CrudPanel Basic Information
        |--------------------------------------------------------------------------
        */
        $this->crud->setModel('App\Models\NewReceta');
        $this->crud->setRoute(config('backpack.base.route_prefix') . '/Recetas');
        $this->crud->setEntityNameStrings('receta', 'recetas');
        $this->crud->enableDetailsRow();
        $this->crud->allowAccess('details_row');

        // select2 filter
        $this->crud->addFilter([
            'name'  => 'active',
            'type'  => 'select2',
            'label' => 'Activo'
        ], function () {
            return [
            0 => 'No',
            1 => 'Si',
            ];
        }, function ($value) { // if the filter is active
            $this->crud->addClause('where', 'active', $value);
        });

        $this->crud->addFilter([
            'name'  => 'editado',
            'type'  => 'select2',
            'label' => 'Editado'
        ], function () {
            return [
            0 => 'No',
            1 => 'Si',
            ];
        }, function ($value) { // if the filter is active
            $this->crud->addClause('where', 'editado', $value);
        });

        /*
        |--------------------------------------------------------------------------
        | CrudPanel Configuration
        |--------------------------------------------------------------------------
        */

        // TODO: remove setFromDb() and manually define Fields and Columns
        //$this->crud->setFromDb();

        $this->crud->addColumn([
            'name' => 'id',
            'label' => 'Id',
        ]);

        $this->crud->addColumn([
            'name' => 'titulo',
            'label' => 'Título',
        ]);

        $this->crud->addColumn([
            'name' => 'like_reactions',
            'label' => 'Me gusta',
        ]);

        $this->crud->addColumn([
            'name' => 'dislike_reactions',
            'label' => 'No me gusta',
        ]);

        $this->crud->addColumn([
            'name' => 'tiempo',
            'label' => 'Tiempo',
            'suffix' => ' minutos'
        ]);

        $this->crud->addColumn([
            'name' => 'porciones',
            'label' => 'Porciones',
        ]);

        $this->crud->addColumn([
            'name' => 'porciones',
            'label' => 'Porciones',
        ]);

        $this->crud->addColumn([
            'name' => 'instrucciones',
            'label' => 'Instrucciones',
        ]);

        $this->crud->addColumn([
            'name' => 'editado',
            'label' => 'Editado',
            'type' => 'boolean',
        ]);

        $this->crud->addColumn([
            'name' => 'active',
            'label' => 'Activo',
            'type' => 'boolean',
        ]);
         $this->crud->addColumn([
            'name' => 'free',
            'label' => 'Free',
            'type' => 'boolean',
        ]);

        $this->crud->addColumn([
            'name' => 'tips',
            'label' => 'Tips',
            // 'type' => 'model_function',
            // 'function_name' => 'getTips'
        ]);

        $this->crud->addColumn([
            'name' => 'imagen_principal',
            'label' => 'Imagen princpial',
            'type' => 'image'
        ]);

        $this->crud->addColumn([
            'name' => 'imagen_secundaria',
            'label' => 'Imagen dentro de la receta',
            'type' => 'image',
            'crop' => true, // set to true to allow cropping, false to disable
            'aspect_ratio' => 1,
        ]);

        $this->crud->addField([
            // 1-n relationship
            'label' => "Buscador de Recetas", // Table column heading
            'type' => "first_search",
            'name' => 'first_search', // the column that contains the ID of that connected entity
            // 'entity' => 'city', // the method that defines the relationship in your Model
            'attribute' => "titulo", // foreign key attribute that is shown to user
            'model' => "App\Models\Receta", // foreign key model
            'data_source' => url("api/receta"), // url to controller search function (with /{id} should return model)
            'placeholder' => "Busca una receta", // placeholder for the select
            'minimum_input_length' => 2, // minimum characters to type before querying results
            'field_url' => 'Recetas/id/edit',
            // 'dependencies'         => [‘category’], // when a dependency changes, this select2 is reset to null
            // ‘method'                    => ‘GET’, // optional - HTTP method to use for the AJAX call (GET, POST)
            // 'include_all_form_fields'  => false, // optional - only send the current field through AJAX (for a smaller payload if you're not using multiple chained select2s)
        ]);

        $this->crud->addField([
            'name' => 'active',
            'label' => 'Activo',
            'type' => 'checkbox',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-4'
            ],
        ]);
       
        $this->crud->addField([
            'name' => 'editado',
            'label' => 'Editado',
            'type' => 'checkbox',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-4'
            ],
        ]);
        $this->crud->addField([
            'name' => 'free',
            'label' => 'Free',
            'type' => 'checkbox',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-4'
            ],
        ]);
        

        $this->crud->addField([
            'name' => 'titulo',
            'label' => 'Titulo',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6'
            ],
        ]);

        $this->crud->addField([
            'name' => 'tiempo',
            'label' => 'Tiempo (en minutos)',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6'
            ],
        ]);

        $this->crud->addField([
            'name' => 'tiempo_nota',
            'label' => 'Nota de Tiempo para la Receta',
            'type' => 'textarea',
            'attributes' => [
                'style' => 'height: 150px'
            ],
        ]);


        $this->crud->addField([
            'name' => 'titulo_ingredientes_resultado',
            'type' => 'custom_html',
            'value' => '<h1>Resultado</h1><h5>(debe de tener al menos un resultado en porciones, sino, este se agregará automáticamente en 2 porciones)</h5>'
        ]);


        $this->crud->addField([
            'name' => 'cantidad_resultado',
            'label' => 'Cantidad',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-4'
            ],
        ]);

        $this->crud->addField([
            'name' => 'medida_id',
            'label' => 'Unidad de medida',
            'type' => 'select2_from_array',
            'options' => Medida::all()->pluck('nombre', 'id')->toArray(), // foreign key model
            //  'options' => array(), // foreign key model
            'wrapperAttributes' => [
                'class' => 'form-group col-md-4'
            ],
        ]);

        $this->crud->addField([
            'name' => 'active_resultado',
            'label' => 'Principal',
            'type' => 'checkbox',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-2',
                'style' => 'padding-top:20px;'
            ],
        ]);

        $this->crud->addField([
            'name' => 'btn_insertar_resultado',
            'type' => 'lista_resultado',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-1'
            ],
            'attributes' => [
                'style' => 'right:0'
            ],
            'value' => '<button style="margin-top: 23px" id="btn_agregar_resultado_receta" type="button" class="btn btn-default">
                           <i style="font-size: 25px" class="fa fa-plus-circle" aria-hidden="true"></i>
                    </button>',
        ]);

        $this->crud->addField([
            'name' => 'table_insertar_resultados',
            'type' => 'custom_html',
            'value' => '<table id="tabla_resultados" class="table table-hover">
                            <thead>
                                   <tr>
                                    <th style="width: 40%">Cantidad</th>
                                    <th style="width: 40%">Unidad de medida</th>
                                    <th style="width: 20%">Principal</th>
                                  </tr>
                            </thead>
                            <tbody></tbody>
                      </table>',
        ]);

        $this->crud->addField([
            'name' => 'titulo_ingredientes',
            'type' => 'custom_html',
            'value' => '<h1>Ingredientes</h1>'
        ]);

        $this->crud->addField([
            'name' => 'ingrediente',
            'label' => 'Ingrediente',
            'type' => 'ingredientes_recetas_field',
            'options_ingredientes' => Ingrediente::all()->pluck('nombre', 'id')->toArray(), // foreign key model
            'options_recetas' => Receta::all()->pluck('titulo', 'id')->toArray(),
            'wrapperAttributes' => [
                'class' => 'form-group col-md-2'
            ],
        ]);

        $this->crud->addField([
            'name' => 'instruccion',
            'label' => 'Instrucción',
            'type' => 'select2_from_array',
            // 'options' => Instruccion::all()->pluck('nombre', 'id')->toArray(), // foreign key model
            'options' => array(),
            'wrapperAttributes' => [
                'class' => 'form-group col-md-2'
            ],
        ]);

        $this->crud->addField([
            'name' => 'cantidad',
            'label' => 'Cantidad',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-2'
            ],
        ]);


        $this->crud->addField([
            'name' => 'medida',
            'label' => 'Medida',
            'type' => 'select2_from_array',
            // 'options' => Medida::all()->pluck('nombre', 'id')->toArray(), // foreign key model
            'options' => array(), // foreign key model
            'wrapperAttributes' => [
                'class' => 'form-group col-md-2'
            ],
        ]);

        $this->crud->addField([
            'name' => 'nota',
            'label' => 'Nota',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-3'
            ],
        ]);

        $this->crud->addField([
            'name' => 'btn_insertar',
            'type' => 'lista_ingredientes',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-1'
            ],
            'attributes' => [
                'style' => 'right:0'
            ],
            'value' => '<button style="margin-top: 23px" id="btn_agregar_ingredientes_receta" type="button" class="btn btn-default">
                            <i style="font-size: 25px" class="fa fa-plus-circle" aria-hidden="true"></i>
                     </button>',
        ]);

        // $this->crud->addField([
        //   'name' => 'nota_tiempo',
        //   'label' => 'Nota de Tiempo',
        //   'wrapperAttributes' => [
        //     'class' => 'form-group col-md-11'
        //   ],
        // ]);


        /*$this->crud->addField([
          'name' => 'unidad_medida',
          'label' => 'Unidad de medida',
          'type' => 'select2_from_array',
          'options' => UnidadMedida::all()->pluck('nombre', 'id'), // foreign key model
          'wrapperAttributes' => [
            'class' => 'form-group col-md-4'
          ],
        ]);*/


        $this->crud->addField([
            'name' => 'table_insertar',
            'type' => 'custom_html',
            'value' => '<table id="tabla_relaciones_ing" class="table table-hover">
                            <thead>
                                   <tr>
                                    <th>#</th>
                                    <th>Ingrediente</th>
                                    <th>Instrucción</th>
                                    <th>Cantidad</th>
                                    <th>Medida</th>
                                    <th>Nota</th>
                                  </tr>
                            </thead>
                            <tbody></tbody>
                      </table>',
        ]);

        // $this->crud->addField([
        //   'name' => 'porciones',
        //   'label' => 'Porciones',
        //   'wrapperAttributes' => [
        //     'class' => 'form-group col-md-4'
        //   ],
        // ]);

        $this->crud->addField([
            'name' => 'instrucciones',
            'label' => 'Instrucciones ("Enter" para agregar una nueva instrucción)',
            'type' => 'textarea',
            'attributes' => [
                'style' => 'height: 200px'
            ],
        ]);

        $this->crud->addField([
            'name' => 'tips',
            'label' => 'Tips ("Enter" para agregar un nuevo Tip)',
            'type' => 'textarea',
            'attributes' => [
                'style' => 'height: 200px'
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-11'
            ]
        ]);

        $this->crud->addField([
            'name' => 'btn_insertar-receta',
            'type' => 'insertar-receta-tips',
            'options_recetas' => Receta::all()->pluck('titulo', 'id')->toArray(),
            'wrapperAttributes' => [
                'class' => 'form-group col-md-1'
            ],
            'attributes' => [
                'style' => 'right:0'
            ],
            'value' => '<button style="margin-top: 23px" id="btn_agregar_receta_tip" type="button" class="btn btn-default">
                           <i style="font-size: 25px" class="fa fa-plus-circle" aria-hidden="true"></i>
                    </button>',
        ]);

        $this->crud->addField([ // image
            'label' => "Imagen principal",
            'name' => "imagen_principal",
            'type' => 'image',
            'upload' => true,
            'crop' => false, // set to true to allow cropping, false to disable
            'aspect_ratio' => 0, // ommit or set to 0 to allow any aspect ratio
            // 'disk' => 'public', // in case you need to show images from a different disk
            // 'prefix' => 'uploads/images/profile_pictures/' // in case your db value is only the file name (no path), you can use this to prepend your path to the image src (in HTML), before it's shown to the user;
        ]);

        $this->crud->addField([
            'name' => 'imagen_secundaria',
            'label' => 'Imagen dentro de la receta',
            'type' => 'image',
            'upload' => true,
            'crop' => true, // set to true to allow cropping, false to disable
            'aspect_ratio' => 2.84,
        ]);

        $this->crud->addField([
            'name' => 'tags',
            'label' => "Tags",
            'type' => 'select2_multiple_aura',
            'entity' => 'tags', // the method that defines the relationship in your Model
            'attribute' => 'nombre', // foreign key attribute that is shown to user
            'model' => "App\Models\Tag", // foreign key model
            'pivot' => true, // on create&update, do you need to add/delete pivot table entries?
            'options'   => (function ($query) {
                return $query->where('type', 'individual')->get();
            }),
        ]);

        $this->crud->addField([
            'name' => 'tags_business',
            'label' => "Tags de Business",
            'type' => 'select2_multiple_aura',
            'entity' => 'business_tags', // the method that defines the relationship in your Model
            'attribute' => 'nombre', // foreign key attribute that is shown to user
            'model' => "App\Models\Tag", // foreign key model
            'pivot' => true, // on create&update, do you need to add/delete pivot table entries?
            'options'   => (function ($query) {
                return $query->where('type', 'business')->get();
            }),
        ]);

        // $this->crud->addField([
        //     'name' => 'equivalences',
        //     'label' => 'Equivalencias'
        // ]);

        $this->crud->addField([   // Hidden
            'name' => 'array_ingredientes',
            'type' => 'hidden',
        ]);

        $this->crud->addField([   // Hidden
            'name' => 'array_resultados',
            'type' => 'hidden',
        ]);

        if (request()->get('receta')) {
            $this->crud->addClause('where', 'id', request()->get('receta'));
        }

        // add asterisk for fields that are required in RecetaRequest
        $this->crud->setRequiredFields(StoreRequest::class, 'create');
        $this->crud->setRequiredFields(UpdateRequest::class, 'edit');
    }

    public function store(StoreRequest $request)
    {
        // dd($request->request);
        // dd($request);

        // $rules = [
        //   'cantidad' => 'required|numeric',
        // ];

        $rules = array();
        $customMessages = array();

        // $customMessages = [
        //   'cantidad.required' => 'La cantidad es requerida.',
        //   'cantidad.numeric' => 'La cantidad debe ser un número.',
        // ];


        $this->validate($request, $rules, $customMessages);

        $ingredientes = (array) json_decode($request->request->get('array_ingredientes'));
        $resultados = (array) json_decode($request->request->get('array_resultados'));

        // dd($ingredientes, $resultados);
        $request->request->remove('array_ingredientes');
        $request->request->remove('array_resultados');
        $request->request->remove('ingrediente');
        $request->request->remove('instruccion');
        $request->request->remove('medida');
        $request->request->remove('unidad_medida_id');
        $request->request->remove('cantidad');
        $request->request->remove('cantidad_resultado');
        $request->request->remove('first_search');
        $request->request->remove('active_resultado');
        $request->request->remove('nota');

        // $request->request->remove('nota_tiempo');
        $request->request->remove('receta-a-tip');
//        $request->request->remove('imagen_principal');

        // your additional operations before save here
        $redirect_location = parent::storeCrud($request);
        // your additional operations after save here
        // use $this->data['entry'] or $this->crud->entry

        // dd($request->all());

        foreach ($ingredientes as $ingrediente) {
            $receta_instruccion_receta = new RecetaInstruccionReceta();
            $receta_instruccion_receta->instruccion_id = $ingrediente->instruccion ? $ingrediente->instruccion : null;
            $receta_instruccion_receta->receta_id = $this->crud->entry->id;
            $receta_instruccion_receta->subreceta_id = $ingrediente->es_ingrediente == false ? $ingrediente->ingrediente : null;
            $receta_instruccion_receta->nota = $ingrediente->nota;
            $receta_instruccion_receta->save();

            $ingrediente_receta = new RecetaInstruccionRecetaMedida();
            $ingrediente_receta->cantidad = $this->fractionToDecimal($ingrediente->cantidad);
            $ingrediente_receta->rec_inst_rec_id = $receta_instruccion_receta->id;
            $ingrediente_receta->medida_id = $ingrediente->medida;
            $ingrediente_receta->save();
        }

        $existe_porcion = false;

        foreach ($resultados as $resultado) {
            // if($resultado->medida == 10){
            $existe_porcion = true;
            // }
            $receta_resultado = new RecetaResultado();
            $receta_resultado->receta_id = $this->crud->entry->id;
            $receta_resultado->medida_id = $resultado->medida;
            $receta_resultado->active = $resultado->active;
            $receta_resultado->cantidad = $this->fractionToDecimal($resultado->cantidad_resultado);
            $receta_resultado->save();
        }

        if (!$existe_porcion && $request->active) { //OBLIGAR A CREAR RESULTADO CON 2 PORCIONES
            $receta_resultado = new RecetaResultado();
            $receta_resultado->receta_id = $this->crud->entry->id;
            $receta_resultado->medida_id = 10; //PORCION
            $receta_resultado->active = 1;
            $receta_resultado->cantidad = 2;
            $receta_resultado->save();
        }

        return $redirect_location;
    }

    public function edit($id)
    {
        $this->crud->hasAccessOrFail('update');
        $this->crud->setOperation('update');

        // get entry ID from Request (makes sure its the last ID for nested resources)
        $id = $this->crud->getCurrentEntryId() ?? $id;

        // get the info for that entry
        $this->data['entry'] = $this->crud->getEntry($id);
        $this->data['crud'] = $this->crud;
        $this->data['saveAction'] = $this->getSaveAction();
        $this->data['fields'] = $this->crud->getUpdateFields($id);
        $this->data['title'] = $this->crud->getTitle() ?? trans('backpack::crud.edit') . ' ' . $this->crud->entity_name;

        $this->data['id'] = $id;

        $array_ingredientes = array();
        $array_resultados = array();
        $receta = Receta::find($id);
        $ingredientes = $receta->recetaInstruccionReceta;
        $resultados = $receta->recetaResultados;

        // dd($resultados, $ingredientes);
        $i = 0;
        foreach ($ingredientes as $ingrediente) {
            // dd($ingrediente->rirm);
            // if($i == 1){
            //   dump($ingrediente->instruccion, $ingrediente->instruccion->ingrediente);
            // }

            $array_ingredientes[] = array(
                'ingrediente' => $ingrediente->instruccion ? $ingrediente->instruccion->ingrediente_id : $ingrediente->subreceta_id,
                'ingrediente_nombre' => $ingrediente->instruccion ? ($ingrediente->instruccion->ingrediente ? $ingrediente->instruccion->ingrediente->nombre : 'No encontrado') : $ingrediente->subreceta['titulo'],
                'cantidad' => $ingrediente->rirm[0]->cantidad,
                'instruccion' => $ingrediente->instruccion_id,
                'instruccion_nombre' => (isset($ingrediente->instruccion['nombre']) ? $ingrediente->instruccion['nombre'] : ''),
                'medida' => $ingrediente->rirm[0]->medida->id,
                'medida_nombre' => $ingrediente->rirm[0]->medida->nombre,
                'nota' => ($ingrediente->nota ? $ingrediente->nota : ''),
                'es_ingrediente' => $ingrediente->subreceta_id ? false : true,
                'order' => $i,
            );
            $i++;
        }

        // dd($array_ingredientes);

        foreach ($resultados as $resultado) {
            $array_resultados[] = array(
                'medida' => $resultado->medida_id,
                'medida_nombre' => $resultado->medida->nombre,
                'cantidad_resultado' => $resultado->cantidad,
                'active' => $resultado->active,
            );
        }

        $this->data['fields']['array_ingredientes']['value'] = json_encode($array_ingredientes);
        // dd($array_resultados);
        $this->data['fields']['array_resultados']['value'] = json_encode($array_resultados);
        $this->data['fields']['medida']['value'] = null;

        // dd($this->data['fields']);

        // $this->data['entry']['array_ingredientes'] = json_encode($array_ingredientes);
        //dd($this->data);
        // load the view from /resources/views/vendor/backpack/crud/ if it exists, otherwise load the one in the package
        return view($this->crud->getEditView(), $this->data);
    }

    public function fractionToDecimal($fraction)
    {
        // Split fraction into whole number and fraction components
        if (is_numeric($fraction)) {
            return $fraction;
        } else {
            preg_match('/^(?P<whole>\d+)?\s?((?P<numerator>\d+)\/(?P<denominator>\d+))?$/', $fraction, $components);

            // Extract whole number, numerator, and denominator components
            $whole = $components['whole'] ?: 0;
            $numerator = $components['numerator'] ?: 0;
            $denominator = $components['denominator'] ?: 0;

            // Create decimal value
            $decimal = $whole;
            $numerator && $denominator && $decimal += ($numerator / $denominator);

            return $decimal;
        }
    }

    public function update(UpdateRequest $request)
    {
        // your additional operations before save here
        // your additional operations after save here
        // use $this->data['entry'] or $this->crud->entry

        // dd($request->request);

        $receta = Receta::find($this->request->request->get('id'));
        $receta->recetaResultados()->delete();

        foreach ($receta->recetaInstruccionReceta as $receta_instruccion) {
            $receta_instruccion->rirm()->delete();
        }

        $receta->recetaInstruccionReceta()->delete();

        $ingredientes = (array) json_decode($request->request->get('array_ingredientes'));
        $resultados = (array) json_decode($request->request->get('array_resultados'));


        // dd($resultados);
        $request->request->remove('array_ingredientes');
        $request->request->remove('array_resultados');
        $request->request->remove('ingrediente');
        $request->request->remove('instruccion');
        $request->request->remove('medida');
        $request->request->remove('unidad_medida_id');
        $request->request->remove('cantidad');
        $request->request->remove('cantidad_resultado');
        $request->request->remove('receta-a-tip');
        $request->request->remove('first_search');
        $request->request->remove('active_resultado');
        $request->request->remove('nota');

//        $request->request->remove('imagen_principal');

        $redirect_location = parent::updateCrud($request);

        $receta = Receta::find($request->id);
        foreach ($receta->recetaInstruccionReceta as $rir) {
            $rir->rirm()->delete();
        };

        $receta->recetaInstruccionReceta()->delete();
        $receta->recetaResultados()->delete();

        foreach ($ingredientes as $ingrediente) {
            $receta_instruccion_receta = new RecetaInstruccionReceta();
            $receta_instruccion_receta->instruccion_id = $ingrediente->instruccion ? $ingrediente->instruccion : null;
            $receta_instruccion_receta->receta_id = $request->id;
            $receta_instruccion_receta->subreceta_id = $ingrediente->es_ingrediente == false ? $ingrediente->ingrediente : null;
            $receta_instruccion_receta->nota = $ingrediente->nota;
            // $receta_instruccion_receta->order = $ingrediente->order;
            $receta_instruccion_receta->save();

            $ingrediente_receta = new RecetaInstruccionRecetaMedida();

            $ingrediente_receta->cantidad = $this->fractionToDecimal($ingrediente->cantidad);
            $ingrediente_receta->rec_inst_rec_id = $receta_instruccion_receta->id;
            $ingrediente_receta->medida_id = $ingrediente->medida;
            $ingrediente_receta->updated_at = null;
            $ingrediente_receta->save();
        }

        $existe_porcion = false;
        // dd($resultados);

        foreach ($resultados as $resultado) {
            if ($resultado->medida == 10) {
                $existe_porcion = true;
            }

            // dd($resultado);

            $receta_resultado = new RecetaResultado();
            $receta_resultado->receta_id = $this->request->request->get('id');
            $receta_resultado->medida_id = $resultado->medida;
            $receta_resultado->active = $resultado->active;
            $receta_resultado->cantidad = $this->fractionToDecimal($resultado->cantidad_resultado);
            $receta_resultado->save();
            // dump($receta_resultado);
        }
        if (!$existe_porcion) { //OBLIGAR A CREAR RESULTADO CON 2 PORCIONES
            $receta_resultado = new RecetaResultado();
            $receta_resultado->receta_id = $this->request->request->get('id');
            $receta_resultado->medida_id = 10; //PORCION
            $receta_resultado->active = 1;
            $receta_resultado->cantidad = 2;
            $receta_resultado->save();
        }

        // dd($resultados);

        $this->sendRecipeToWeb($receta);
        return $redirect_location;
    }

    public function destroy($id)
    {
        $receta = Receta::find($id);
        $resultados = $receta->recetaResultados;

        // dd($resultados, $receta);

        if ($resultados) {
            foreach ($resultados as $resultado) {
                $resultado->delete();
            }
        }

        return $this->crud->delete($id);
    }

    public function ingredienteMedida($ing)
    {
        $ingrediente = Ingrediente::find($ing);
        if ($ingrediente) {
            return $ingrediente->instrucciones ? $ingrediente->instrucciones : array();
        }
        return array();
    }

    public function instruccionMedida(Request $request)
    {
        $medida = array();
        //NC
        $tiposMedidaNC = Medida::where('tipo_medida_id', '=', 4)->get();
        foreach ($tiposMedidaNC as $tiposMedidaCompatible) {
            $medida[] = $tiposMedidaCompatible;
        }

        $ingrediente = Ingrediente::find($request->get('ingrediente'));
        $instruccion = Instruccion::find($request->get('instruccion'));

        // return $instruccion;
        // return $instruccion;

        if ($instruccion->medida->tipo_medida_id == 1) {
            $tiposMedidaCompatibles = Medida::where('tipo_medida_id', '=', $instruccion->medida->tipo_medida_id)->get();
            foreach ($tiposMedidaCompatibles as $tiposMedidaCompatible) {
                $medida[] = $tiposMedidaCompatible;
            }
        }
        $medida[] = $instruccion ? $instruccion->medida : null;

        return $medida;
    }

    public function recetaMedida($id)
    {
        $medida = array();
        //NC
        $tiposMedidaNC = Medida::where('tipo_medida_id', '=', 4)->get();
        foreach ($tiposMedidaNC as $tiposMedidaCompatible) {
            $medida[] = $tiposMedidaCompatible;
        }

        $recetaResultados = RecetaResultado::where('receta_id', $id)->get();
        foreach ($recetaResultados as $recetaResultado) {
            // dd($recetaResultado);
            if ($recetaResultado->medida->tipo_medida_id == 1) {
                $tiposMedidaCompatibles = Medida::where('tipo_medida_id', '=', $recetaResultado->medida->tipo_medida_id)->get();
                foreach ($tiposMedidaCompatibles as $tiposMedidaCompatible) {
                    $medida[] = $tiposMedidaCompatible;
                }
            } else {
                $medida[] = $recetaResultado->medida;
            }
        }

        return $medida;

        // $medidas_array = array();
        // $tipo_medidas_array = array();

        // foreach ($recetas as $receta) {
        //   $tipo_medida = $receta->medida->tipoMedida;
        //   if(!array_search($tipo_medida, $tipo_medidas_array)) {
        //     $tipo_medidas_array[] = $tipo_medida;
        //     $medidas_array[] = $tipo_medida->medidas;
        //   }
        // }

        // return $medidas_array;
    }

    public function sendRecipeToWeb($recipe)
    {
        try {
            $response = file_get_contents('http://healthymartinaweb.test/create-entries/' . rawurlencode($recipe->toJson()));
            if ($response == "OK") {
                return "Success";
            } else {
                return "Failed";
            }
        } catch (Exception $e) {
            report($e);

            return "Failed";
        }
    }
}
