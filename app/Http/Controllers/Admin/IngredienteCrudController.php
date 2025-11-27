<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;

// VALIDATION: change the requests to match your own file names if you need form validation
use App\Http\Requests\IngredienteRequest as StoreRequest;
use App\Http\Requests\IngredienteRequest as UpdateRequest;
use Backpack\CRUD\CrudPanel;
use App\Models\Instruccion;
use App\Models\Ingrediente;
use App\Models\Medida;


/**
 * Class IngredienteCrudController
 * @package App\Http\Controllers\Admin
 * @property-read CrudPanel $crud
 */
class IngredienteCrudController extends CrudController
{
    public function setup()
    {
        /*
        |--------------------------------------------------------------------------
        | CrudPanel Basic Information
        |--------------------------------------------------------------------------
        */
        $this->crud->setModel('App\Models\Ingrediente');
        $this->crud->setRoute(config('backpack.base.route_prefix') . '/Ingredientes');
        $this->crud->setEntityNameStrings('ingrediente', 'ingredientes');

        /*
        |--------------------------------------------------------------------------
        | CrudPanel Configuration
        |--------------------------------------------------------------------------
        */

        // TODO: remove setFromDb() and manually define Fields and Columns
        // $this->crud->setFromDb();

        $this->crud->addColumn([
          'name' => 'nombre',
          'label' => 'Nombre'
        ]);

        $this->crud->addColumn([
            'name' => 'categoria.nombre',
            'label' => 'Categoría',
            // 'orderable' => true,e
            'type' => "select",
            'name' => 'categoria_id', // the column that contains the ID of that connected entity;
            'entity' => 'categoria', // the method that defines the relationship in your Model
            'attribute' => "nombre", // foreign key attribute that is shown to user
            'model' => "App\Models\Categoria", // foreign key model
        ]);

        $this->crud->addColumn([
            'name' => 'fdc',
            'label' => 'FDC',
        ]);

        $this->crud->addColumn([
            // run a function on the CRUD model and show its return value
            'name' => "num_recetas",
            'label' => "# de Recetas", // Table column heading
            'type' => "model_function",
            'function_name' => 'getApareceEn', // the method in your Model
            // 'function_parameters' => [$one, $two], // pass one/more parameters to that method
            // 'limit' => 100, // Limit the number of characters shown
         ]);

        // $this->crud->addColumn([
        //     // run a function on the CRUD model and show its return value
        //     'name' => "ver_recetas",
        //     'label' => "Recetas", // Table column heading
        //     'type' => "model_function",
        //     'function_name' => 'getRecetasButton', // the method in your Model
        //     // 'function_parameters' => [$one, $two], // pass one/more parameters to that method
        //     // 'limit' => 100, // Limit the number of characters shown
        // ]);

        $this->crud->addField([
            // 1-n relationship
            'label' => "Buscador de ingredientes", // Table column heading
            'type' => "first_search",
            'name' => 'first_search', // the column that contains the ID of that connected entity
            // 'entity' => 'city', // the method that defines the relationship in your Model
            'attribute' => "nombre", // foreign key attribute that is shown to user
            // 'model' => "App\Models\Ingrediente", // foreign key model
            'data_source' => url("api/ingrediente"), // url to controller search function (with /{id} should return model)
            'placeholder' => "Busca un ingrediente", // placeholder for the select
            'minimum_input_length' => 2, // minimum characters to type before querying results
            'field_url' => 'Ingredientes/id/edit',
            // 'dependencies'         => [‘category’], // when a dependency changes, this select2 is reset to null
            // ‘method'                    => ‘GET’, // optional - HTTP method to use for the AJAX call (GET, POST)
        // 'include_all_form_fields'  => false, // optional - only send the current field through AJAX (for a smaller payload if you're not using multiple chained select2s)
        ]);
        

        $this->crud->addField([
            'name' => 'nombre',
            'label' => 'Nombre del ingrediente',
            'type' => 'text',
        ]);

        $this->crud->addField([
            'name' => 'categoria_id',
            'label' => 'Categoria',
            'type' => 'select2',
            'entity' => 'categoria',
            'attribute' => 'nombre',
            'model' => 'App\Models\Categoria',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-3'
            ],
        ]);

        $this->crud->addField([
            'name' => 'tipo_medida_id',
            'label' => 'Tipo Medida',
            'type' => 'select2',
            'entity' => 'tipo_medida',
            'attribute' => 'nombre',
            'model' => 'App\Models\TipoMedida',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-3'
            ],
        ]);

        // dd($entry);

        if ($this->crud) {
            $this->crud->addField([
                'name' => 'fdc',
                'label' => 'FDC API',
                'type' => 'fdc_ajax',
                'related' => 'fdc_raw',
                'related_name' => 'fdc_name',
                'placeholder' => 'Ingresa el código de FDC',
                'minimum_input_length' => '3',
                'data_source' => '/admin/getFDCData',
                'data_detail' => '/admin/getFDCFood',
                'attribute' => '/name',
                'id' => 'fdc_ajax',
                'default_name'=> 'fdc_name',
                'wrapperAttributes' => [
                    'class' => 'form-group col-md-5'
                ],
            ]);
        }
        
        else{
            $this->crud->addField([
                'name' => 'fdc',
                'label' => 'FDC API',
                'type' => 'fdc_ajax',
                'related' => 'raw_fda',
                'placeholder' => 'Ingresa el código de FDC',
                'minimum_input_length' => '3',
                'data_source' => '/admin/getFDCData',
                'data_detail' => '/admin/getFDCFood',
                'attribute' => '/name',
                'id' => 'fdc_ajax',
                'wrapperAttributes' => [
                    'class' => 'form-group col-md-5'
                ],
            ]);
        }
        

        $this->crud->addField([
            'name' => 'fdc_detail_name',
            'label' => '',
            'type' => 'fdc_detail',
            'related' => 'fdc',
            'minimum_input_length' => '3',
            'data_source' => '/admin/getFDCData',
            // 'data_detail' => '/admin/getFDCFood',
            'attribute' => '/name',
            'id' => 'fdc_detail',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-1'
            ],
        ]);
        

        $this->crud->addField([
            'name' => 'fdc_raw',
            'label' => '',
            'type' => 'hidden',
        ]);

        $this->crud->addField([
            'name' => 'fdc_name',
            'label' => '',
            'type' => 'hidden',
        ]);

        $this->crud->addField([
            'name' => 'divisor',
            'type' => 'custom_html',
            'value' => '<div></div>',
        ]);
        // $this->crud->addField([
        //     'name' => 'instrucciones_fdc',
        //     'label' => 'Instrucciones desde FDC',
        //     'type' => 'select2_from_array',
        //     'options' => array(),
        //     'wrapperAttributes' => [
        //         'class' => 'form-group col-md-11'
        //       ],
        // ]);

        // $this->crud->addField([
        //     'name' => 'btn_insertar',
        //     'type' => 'lista_ingredientes',
        //     'wrapperAttributes' => [
        //         'class' => 'form-group col-md-1'
        //       ],
        //     'attributes' => [
        //       'style' => 'right:0'
        //     ],
        //     'value' => '<button style="margin-top: 23px" id="btn_agregar_ingredientes_receta" type="button" class="btn btn-default">
        //                      <i style="font-size: 25px" class="fa fa-plus-circle" aria-hidden="true"></i>
        //               </button>',
        // ]);
        
        // $this->crud->addField([
        //     'name' => 'sustitucion',
        //     'label' => 'Sustitucion',
        //     'type' => 'text',
        // ]);
        $this->crud->addField([
            'name' => 'divisor',
            'type' => 'custom_html',
            'value' => '<div>Si el ingrediente no tiene una instrucción o se requiere una instrucción vacia, colocar NA, para que no se visualice en el sitio web.</div>',
        ]);
        $this->crud->addField([
            'name' => 'instruccion',
            'label' => 'Nota de Preparación',
            'type' => 'text',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-3'
            ],
        ]);

        $this->crud->addField([
            'name' => 'sin_conversion',
            'label' => 'Esta nota de preparación no tiene una conversión',
            'type' => 'checkbox',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-2'
            ],
        ]);

        $this->crud->addField([
            'name' => 'cantidad',
            'label' => 'Cantidad',
            'type' => 'text',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-1'
            ],
        ]);

        $this->crud->addField([
            'name' => 'medida',
            'label' => 'Medida',
            'type' => 'select2_from_array',
            'options' => Medida::all()->pluck('nombre', 'id')->toArray(), // foreign key model
            'wrapperAttributes' => [
              'class' => 'form-group col-md-2'
            ],
          ]);

        $this->crud->addField([
            'name' => 'equivalencia',
            'label' => 'Equivalencia a tipo de medida',
            'type' => 'text',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-3'
            ],
        ]);
  
        $this->crud->addField([
            'name' => 'btn_insertar',
            'type' => 'lista_instrucciones',
            'wrapperAttributes' => [
                 'class' => 'form-group col-md-1'
               ],
            'attributes' => [
               'style' => 'right:0'
            ],
            'value' => '<button style="margin-top: 23px" id="btn_agregar_instrucciones" type="button" class="btn btn-default">
                              <i style="font-size: 25px" class="fa fa-plus-circle" aria-hidden="true"></i>
                       </button>',
        ]);
        $this->crud->addField([
            'name' => 'array_instrucciones',
            'label' => '',
            'type' => 'hidden',
        ]);
        $this->crud->addField([
            'name' => 'table_insertar',
            'type' => 'custom_html',
            'value' => '<table id="tabla_instrucciones" class="table table-hover">
                             <thead>
                                    <tr>
                                     <th>#</th>
                                     <th>Nota de Preparación</th>
                                     <th>Cantidad</th>
                                     <th>Medida</th>
                                     <th>Equivalencia en tipo de medida</th>
                                   </tr>
                             </thead>
                             <tbody>
                             </tbody>
                       </table>',
        ]);

        // $this->crud->addField([
        //     'name' => 'equivalencia_medida_compra',
        //     'label' => 'Equivalencia Medida Compra',
        //     'type' => 'text',
        //     'wrapperAttributes' => [
        //         'class' => 'form-group col-md-6'
        //     ],
        // ]);
        //
        // $this->crud->addField([
        //     'label' => 'Medida',
        //     'type' => 'select2',
        //     'name' => 'equivalencia_medida_id',
        //     'entity' => 'medidas',
        //     'attribute' => 'nombre',
        //     'model' => 'App\Models\Medida',
        //     'wrapperAttributes' => [
        //         'class' => 'form-group col-md-6'
        //     ],
        // ]);

        // $this->crud->addColumn([
        //     'name' => 'usda',
        //     'label' => 'USDA',
        // ]);

        // $this->crud->addField([
        //     'name' => 'forma_compra_id',
        //     'label' => 'Forma de compra',
        //     'type' => 'select2',
        //     'entity' => 'formaCompra',
        //     'attribute' => 'nombre',
        //     'model' => 'App\Models\FormaCompra',
        //     'wrapperAttributes' => [
        //         'class' => 'form-group col-md-6'
        //     ],
        // ]);



        // $this->crud->addField([
        //     'label' => 'Medida',
        //     'type' => 'select2',
        //     'name' => 'medida_id',
        //     'entity' => 'medida',
        //     'attribute' => 'nombre',
        //     'model' => 'App\Models\Medida',
        //     'wrapperAttributes' => [
        //         'class' => 'form-group col-md-6'
        //     ],
        // ]);

        $this->crud->addField([
            'name' => 'Titulo',
            'type' => 'custom_html',
            'value' => '<h3>Forma de compra en supermercado</h3>',
        ]);
        $this->crud->addField([
            'name' => 'divisor2',
            'type' => 'custom_html',
            'value' => '<div>Si el ingrediente es comprado directamente en gramos o mililitros, no colocar nada en esta equivalencia</div>',
        ]);
        $this->crud->addField([
            'label' => 'Cantidad en tipo de medida',
            'type' => 'text',
            'name' => 'cantidad_gramos',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-3'
            ],
        ]);

        $this->crud->addField([
            'name' => 'separador_equivalencia',
            'type' => 'custom_html',
            'value' => '<h3>Equivalentes a:</h3>',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-3'
            ],
        ]);

        // $this->crud->addField([
        //     'label' => 'Unidad de medida',
        //     'type' => 'select2_from_array',
        //     'name' => 'unidad_medida_id',
        //     'options' => array(),
        //     'entity' => 'unidadMedida',
        //     'attribute' => 'nombre',
        //     'model' => 'App\Models\Medida',
        //     'wrapperAttributes' => [
        //         'class' => 'form-group col-md-3'
        //     ],
        // ]);

        $this->crud->addField([
            'label' => 'Cantidad',
            'type' => 'text',
            'name' => 'cantidad_forma_compra',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-3'
            ],
        ]);

        $this->crud->addField([  // Select2
                'label' => "Medida",
                'type' => 'select2',
                'name' => 'forma_compra_id', // the db column for the foreign key
                'entity' => 'forma_compra', // the method that defines the relationship in your Model
                'attribute' => 'nombre', // foreign key attribute that is shown to user
                'model' => "App\Models\Medida", // foreign key model
                'wrapperAttributes' => [
                    'class' => 'form-group col-md-3'
                ]
        ]);

        // $this->crud->addColumn([
        //     'name' => 'unidad_medida',
        //     'label' => 'Unidad de medida',
        // ]);


        // $this->crud->addField([
        //   'label' => "Tipos de medidas",
        //   'type' => 'select2_multiple',
        //   'name' => 'tiposMedida', // the method that defines the relationship in your Model
        //   'entity' => 'tiposMedida', // the method that defines the relationship in your Model
        //   'attribute' => 'nombre', // foreign key attribute that is shown to user
        //   'model' => "App\Models\TipoMedida", // foreign key model
        //   'pivot' => true, // on create&update, do you need to add/delete pivot table entries?
        // ]);

        $this->crud->addButtonFromModelFunction('line', 'instrucciones', 'getRecetasButton', 'start');
        // $this->crud->addButtonFromModelFunction('line', 'medidas', 'medidasButton', 'end');
        //$this->crud->addButtonFromModelFunction('top', 'deleteOnes', 'deleteOnes', 'top');


        // $this->crud->addField([
        //   'name' => 'unidad_medida_id',
        //   'label' => 'Unidad de medida',
        //   'type' => 'select2',
        //   'entity' => 'unidadMedida', // the method that defines the relationship in your Model
        //   'attribute' => 'nombre', // foreign key attribute that is shown to user
        //   'model' => "App\Models\UnidadMedida", // foreign key model
        //   'wrapperAttributes' => [
        //     'class' => 'form-group col-md-6'
        //   ],
        // ]);

        // add asterisk for fields that are required in IngredienteRequest
        $this->crud->setRequiredFields(StoreRequest::class, 'create');
        $this->crud->setRequiredFields(UpdateRequest::class, 'edit');
    }

    public function destroy($id){
        $ingrediente = Ingrediente::find($id);
        if($ingrediente->getApareceEn() == 0){
            $this->crud->hasAccessOrFail('delete');
            return $this->crud->delete($id);
        }
        else{
            return false;
        }
    }

    public function store(StoreRequest $request)
    {
        // dd($request->request);
        $request->request->remove('instruccion');
        $request->request->remove('cantidad');
        $request->request->remove('medida');
        $request->request->remove('equivalencia');
        $request->request->remove('sin_conversion');
        $request->request->remove('first_search');
        $instrucciones = collect((array) json_decode($request->request->get('array_instrucciones')));
        $request->request->remove('array_instrucciones');
        // your additional operations before save here
        $redirect_location = parent::storeCrud($request);
        // your additional operations after save here
        // use $this->data['entry'] or $this->crud->entry

        $anterioresInstrucciones = $this->crud->entry->instrucciones;
        // dump($anterioresInstrucciones);
        // dump($instrucciones);
        foreach($instrucciones as $instruccion){
            //instruccion nueva
            if($instruccion->id == -1){
                // dd($instruccion);
                $instruccionNueva = new Instruccion();
                $instruccionNueva->nombre = $instruccion->instruccion;
                if($instruccion->sin_conversion){
                    $instruccionNueva->sin_conversion = $instruccion->sin_conversion;
                    //Forzar a 1
                    $instruccionNueva->cantidad = 1;
                    $instruccionNueva->equivalencia_gramos = 1;
                    $instruccionNueva->medida_id = 7;
                }
                else{
                    $instruccionNueva->sin_conversion = $instruccion->sin_conversion;
                    $instruccionNueva->cantidad = $instruccion->cantidad;
                    $instruccionNueva->equivalencia_gramos = $instruccion->equivalencia_gramos;
                    $instruccionNueva->medida_id = $instruccion->medida_id;
                }
                $instruccionNueva->ingrediente_id = $this->crud->entry->id;
                $instruccionNueva->save();
                // dd($instruccionNueva);
            }
            //Instrucción existe, proximo a implementar edición, comparando datos
            else{
                // dd($anterioresInstrucciones->search($instruccion->id));
            }
            
        }
        // dump($instrucciones->where('id','>',-1)->pluck('id')->toArray());
        $instruccionesEliminar = $anterioresInstrucciones->except($instrucciones->where('id','>',-1)->pluck('id')->toArray());
        foreach($instruccionesEliminar as $instruccionEliminar){
            $instruccionEliminar->delete();
        }
        // dd();
        // your additional operations after save here
        // use $this->data['entry'] or $this->crud->entry

        return $redirect_location;
    }

    public function edit($id){
        $this->crud->hasAccessOrFail('update');
        $this->crud->setOperation('update');
        
        // get entry ID from Request (makes sure its the last ID for nested resources)
        $id = $this->crud->getCurrentEntryId() ?? $id;

        // get the info for that entry
        $this->data['entry'] = $this->crud->getEntry($id);
        $this->data['crud'] = $this->crud;
        $this->data['saveAction'] = $this->getSaveAction();
        $this->data['fields'] = $this->crud->getUpdateFields($id);
        $this->data['title'] = $this->crud->getTitle() ?? trans('backpack::crud.edit').' '.$this->crud->entity_name;

        $this->data['id'] = $id;

        $array_instrucciones = array();
        $ingrediente = Ingrediente::find($id);
        $i = 1;
        // dd($ingrediente->instrucciones);
        foreach ($ingrediente->instrucciones as $instruccion) {
          // dd($ingrediente->rirm);
            $array_instrucciones[] = array(
              'order' => $i,
              'id' => $instruccion->id,
              'instruccion' => $instruccion->nombre,
              'sin_conversion' => $instruccion->sin_conversion,
              'cantidad' => (!$instruccion->sin_conversion ? $instruccion->cantidad : 'Sin conversión'),
              'medida_id' => $instruccion->medida_id,
              'medida_nombre' => ($instruccion->medida_id ? $instruccion->medida->nombre : ''),
              'equivalencia_gramos' => ($instruccion->equivalencia_gramos ? $instruccion->equivalencia_gramos : ''),
            //   'order' => ($ingrediente->order==-1 ? $i : $ingrediente->order)
            );
            $i++;
        }
        // dd($array_instrucciones);


        $this->data['fields']['array_instrucciones']['value'] = json_encode($array_instrucciones);
        
        // dd($this->data['fields']);

        // $this->data['entry']['array_instrucciones'] = json_encode($array_instrucciones);
        //dd($this->data);
        // load the view from /resources/views/vendor/backpack/crud/ if it exists, otherwise load the one in the package
        return view($this->crud->getEditView(), $this->data);

    }

    public function update(UpdateRequest $request)
    {
        $request->request->remove('instruccion');
        $request->request->remove('cantidad');
        $request->request->remove('medida');
        $request->request->remove('equivalencia');
        $request->request->remove('sin_conversion');
        $request->request->remove('first_search');

        $instrucciones = collect((array) json_decode($request->request->get('array_instrucciones')));
        $request->request->remove('array_instrucciones');

        // your additional operations before save here
        $redirect_location = parent::updateCrud($request);
        $anterioresInstrucciones = $this->crud->entry->instrucciones;
        // dump($anterioresInstrucciones);
        // dump($instrucciones);
        foreach($instrucciones as $instruccion){
            $instru = Instruccion::find($instruccion->id);
            //instruccion nueva
            if(!$instru){
                // dump('nueva');
                $instru = new Instruccion();
            }
            
            // dump($instru);
            $instru->nombre = $instruccion->instruccion;
            if($instruccion->sin_conversion){
                $instru->sin_conversion = $instruccion->sin_conversion;
                $instru->cantidad = 1;
                $instru->equivalencia_gramos = 1;
                $instru->medida_id = 7;
            }
            else{
                $instru->sin_conversion = $instruccion->sin_conversion;
                $instru->cantidad = $instruccion->cantidad;
                $instru->equivalencia_gramos = $instruccion->equivalencia_gramos;
                $instru->medida_id = $instruccion->medida_id;
            }
            $instru->ingrediente_id = $this->crud->entry->id;
            $instru->save();            
        }
        // dump($instrucciones->where('id','>',-1)->pluck('id')->toArray());
        $instruccionesEliminar = $anterioresInstrucciones->except($instrucciones->where('id','>',-1)->pluck('id')->toArray());
        foreach($instruccionesEliminar as $instruccionEliminar){
            $instruccionEliminar->delete();
        }
        // dd();
        // your additional operations after save here
        // use $this->data['entry'] or $this->crud->entry
        return $redirect_location;
    }

    public function getFDCData(){
        $query = $_GET['q'];
        $api_key = 'CfnMZ0u5ron25riOIPl55MzLxuGzs4U6ZKphtKfC';
        $url = 'https://api.nal.usda.gov/fdc/v1/foods/search?api_key='.$api_key.'&query='.urlencode($query);
        $response = file_get_contents($url);
        $response = json_decode($response);
        $responseArray = array();

        foreach($response->foods as $food){
            $tmpArray = new \stdClass();
            $tmpArray->id = $food->fdcId;
            if(isset($food->ingredients)){
                $ingredientesCount = count(explode(',',$food->ingredients));
            }
            else{
                $ingredientesCount = 'NA';
            }
            $tmpArray->text = $food->fdcId.' - '.$food->description.' - '.$food->dataType.' - Ing: '.$ingredientesCount.' - Nut: '.count($food->foodNutrients);
            $responseArray['results'][] = $tmpArray;
        }

        $responseArray['pagination']['more'] = false;

        return response()->json($responseArray);
    }

    public function getFDCFood(){
        $query = $_GET['q'];
        $api_key = 'CfnMZ0u5ron25riOIPl55MzLxuGzs4U6ZKphtKfC';
        $url = 'https://api.nal.usda.gov/fdc/v1/food/'.$query.'?api_key='.$api_key;
        $response = file_get_contents($url);
        $response = json_decode($response);
        // $responseArray = array();

        // foreach($response->foods as $food){
        //     $tmpArray = new \stdClass();
        //     $tmpArray->id = $food->fdcId;
        //     $tmpArray->text = $food->fdcId.' - '.$food->description;
        //     $responseArray['results'][] = $tmpArray;
        // }

        // $responseArray['pagination']['more'] = false;

        return response()->json($response);
    }
}
//https://api.nal.usda.gov/fdc/v1/food/167568?api_key=CfnMZ0u5ron25riOIPl55MzLxuGzs4U6ZKphtKfC