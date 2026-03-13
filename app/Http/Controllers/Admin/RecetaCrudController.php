<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

use App\Http\Requests\RecetaRequest as StoreRequest; // used in setupCreateOperation validation
use App\Http\Requests\RecetaRequest as UpdateRequest; // used in setupUpdateOperation validation
use App\Models\NewReceta as Receta;
use App\Models\Ingrediente;
use App\Models\Instruccion;
use App\Models\Medida;
use App\Models\RecetaInstruccionReceta;
use App\Models\RecetaInstruccionRecetaMedida;
use App\Models\RecetaResultado;
use Illuminate\Http\Request;

/**
 * Class RecetaCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class RecetaCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\NewReceta::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/Recetas');
        CRUD::setEntityNameStrings('receta', 'recetas');

        CRUD::enableDetailsRow();
        CRUD::allowAccess('details_row');
    }

    protected function setupListOperation()
    {
        CRUD::addFilter([
            'name'  => 'active',
            'type'  => 'select2',
            'label' => 'Activo',
        ], function () {
            return [0 => 'No', 1 => 'Si'];
        }, function ($value) {
            CRUD::addClause('where', 'active', $value);
        });

        CRUD::addFilter([
            'name'  => 'editado',
            'type'  => 'select2',
            'label' => 'Editado',
        ], function () {
            return [0 => 'No', 1 => 'Si'];
        }, function ($value) {
            CRUD::addClause('where', 'editado', $value);
        });

        CRUD::addColumn(['name' => 'id',               'label' => 'ID']);
        CRUD::addColumn(['name' => 'titulo',            'label' => 'Título']);
        CRUD::addColumn(['name' => 'like_reactions',    'label' => 'Me gusta']);
        CRUD::addColumn(['name' => 'dislike_reactions', 'label' => 'No me gusta']);
        CRUD::addColumn(['name' => 'tiempo',            'label' => 'Tiempo', 'suffix' => ' minutos']);
        CRUD::addColumn(['name' => 'porciones',         'label' => 'Porciones']);
        CRUD::addColumn(['name' => 'editado',           'label' => 'Editado', 'type' => 'boolean']);
        CRUD::addColumn(['name' => 'active',            'label' => 'Activo',  'type' => 'boolean']);
        CRUD::addColumn(['name' => 'free',              'label' => 'Gratis',  'type' => 'boolean']);
        CRUD::addColumn(['name' => 'imagen_principal',  'label' => 'Imagen Principal', 'type' => 'image']);
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation(StoreRequest::class);

        // ── Flags ──────────────────────────────────────────────────────────
        CRUD::addField(['name' => 'active',  'label' => 'Activo',  'type' => 'checkbox', 'wrapperAttributes' => ['class' => 'form-group col-md-4']]);
        CRUD::addField(['name' => 'editado', 'label' => 'Editado', 'type' => 'checkbox', 'wrapperAttributes' => ['class' => 'form-group col-md-4']]);
        CRUD::addField(['name' => 'free',    'label' => 'Gratis',  'type' => 'checkbox', 'wrapperAttributes' => ['class' => 'form-group col-md-4']]);

        // ── Basic info ─────────────────────────────────────────────────────
        CRUD::addField(['name' => 'titulo',     'label' => 'Título',           'type' => 'text',    'wrapperAttributes' => ['class' => 'form-group col-md-6']]);
        CRUD::addField(['name' => 'tiempo',     'label' => 'Tiempo (minutos)', 'type' => 'number',  'wrapperAttributes' => ['class' => 'form-group col-md-6']]);
        CRUD::addField(['name' => 'tiempo_nota','label' => 'Nota de Tiempo',   'type' => 'textarea','attributes' => ['style' => 'height:150px']]);
        CRUD::addField(['name' => 'descripcion','label' => 'Descripción',      'type' => 'textarea']);

        // ── Resultado ──────────────────────────────────────────────────────
        CRUD::addField([
            'name'  => 'titulo_resultado',
            'type'  => 'custom_html',
            'value' => '<h3>Resultado</h3><small>(debe de tener al menos un resultado en porciones, sino se agregará automáticamente en 2 porciones)</small>',
        ]);
        CRUD::addField(['name' => 'cantidad_resultado', 'label' => 'Cantidad', 'wrapperAttributes' => ['class' => 'form-group col-md-4']]);
        CRUD::addField([
            'name'    => 'medida_id',
            'label'   => 'Unidad de medida',
            'type'    => 'select2_from_array',
            'options' => Medida::all()->pluck('nombre', 'id')->toArray(),
            'wrapperAttributes' => ['class' => 'form-group col-md-4'],
        ]);
        CRUD::addField([
            'name'  => 'active_resultado',
            'label' => 'Principal',
            'type'  => 'checkbox',
            'wrapperAttributes' => ['class' => 'form-group col-md-2', 'style' => 'padding-top:20px;'],
        ]);
        CRUD::addField([
            'name'  => 'btn_insertar_resultado',
            'type'  => 'lista_resultado',
            'wrapperAttributes' => ['class' => 'form-group col-md-1'],
            'value' => '<button style="margin-top:23px" id="btn_agregar_resultado_receta" type="button" class="btn btn-default">
                            <i style="font-size:25px" class="fa fa-plus-circle" aria-hidden="true"></i>
                        </button>',
        ]);
        CRUD::addField([
            'name'  => 'table_resultados',
            'type'  => 'custom_html',
            'value' => '<table id="tabla_resultados" class="table table-hover">
                            <thead><tr>
                                <th style="width:40%">Cantidad</th>
                                <th style="width:40%">Unidad de medida</th>
                                <th style="width:20%">Principal</th>
                            </tr></thead>
                            <tbody></tbody>
                        </table>',
        ]);

        // ── Ingredientes ───────────────────────────────────────────────────
        CRUD::addField([
            'name'  => 'titulo_ingredientes',
            'type'  => 'custom_html',
            'value' => '<h3>Ingredientes</h3>',
        ]);
        CRUD::addField([
            'name'                => 'ingrediente',
            'label'               => 'Ingrediente',
            'type'                => 'ingredientes_recetas_field',
            'options_ingredientes' => Ingrediente::orderBy('nombre')->pluck('nombre', 'id')->toArray(),
            'options_recetas'     => Receta::orderBy('titulo')->pluck('titulo', 'id')->toArray(),
            'wrapperAttributes'   => ['class' => 'form-group col-md-2'],
        ]);
        CRUD::addField([
            'name'    => 'instruccion',
            'label'   => 'Instrucción',
            'type'    => 'select2_from_array',
            'options' => [],
            'wrapperAttributes' => ['class' => 'form-group col-md-2'],
        ]);
        CRUD::addField(['name' => 'cantidad', 'label' => 'Cantidad', 'wrapperAttributes' => ['class' => 'form-group col-md-2']]);
        CRUD::addField([
            'name'    => 'medida',
            'label'   => 'Medida',
            'type'    => 'select2_from_array',
            'options' => [],
            'wrapperAttributes' => ['class' => 'form-group col-md-2'],
        ]);
        CRUD::addField(['name' => 'nota', 'label' => 'Nota', 'wrapperAttributes' => ['class' => 'form-group col-md-3']]);
        CRUD::addField([
            'name'  => 'btn_insertar',
            'type'  => 'lista_ingredientes',
            'wrapperAttributes' => ['class' => 'form-group col-md-1'],
            'value' => '<button style="margin-top:23px" id="btn_agregar_ingredientes_receta" type="button" class="btn btn-default">
                            <i style="font-size:25px" class="fa fa-plus-circle" aria-hidden="true"></i>
                        </button>',
        ]);
        CRUD::addField([
            'name'  => 'table_insertar',
            'type'  => 'custom_html',
            'value' => '<table id="tabla_relaciones_ing" class="table table-hover">
                            <thead><tr>
                                <th>#</th><th>Ingrediente</th><th>Instrucción</th>
                                <th>Cantidad</th><th>Medida</th><th>Nota</th>
                            </tr></thead>
                            <tbody></tbody>
                        </table>',
        ]);

        // ── Instructions & Tips ────────────────────────────────────────────
        CRUD::addField([
            'name'       => 'instrucciones',
            'label'      => 'Instrucciones ("Enter" para agregar una nueva instrucción)',
            'type'       => 'textarea',
            'attributes' => ['style' => 'height:200px'],
        ]);
        CRUD::addField([
            'name'       => 'tips',
            'label'      => 'Tips ("Enter" para agregar un nuevo Tip)',
            'type'       => 'textarea',
            'attributes' => ['style' => 'height:200px'],
            'wrapperAttributes' => ['class' => 'form-group col-md-11'],
        ]);
        CRUD::addField([
            'name'            => 'btn_insertar-receta',
            'type'            => 'insertar-receta-tips',
            'options_recetas' => Receta::orderBy('titulo')->pluck('titulo', 'id')->toArray(),
            'wrapperAttributes' => ['class' => 'form-group col-md-1'],
            'value'           => '<button style="margin-top:23px" id="btn_agregar_receta_tip" type="button" class="btn btn-default">
                                      <i style="font-size:25px" class="fa fa-plus-circle" aria-hidden="true"></i>
                                  </button>',
        ]);

        // ── Images ─────────────────────────────────────────────────────────
        CRUD::addField([
            'label'        => 'Imagen principal',
            'name'         => 'imagen_principal',
            'type'         => 'image',
            'upload'       => true,
            'crop'         => false,
            'aspect_ratio' => 0,
        ]);
        CRUD::addField([
            'name'         => 'imagen_secundaria',
            'label'        => 'Imagen dentro de la receta',
            'type'         => 'image',
            'upload'       => true,
            'crop'         => true,
            'aspect_ratio' => 2.84,
        ]);

        // ── Nutritional ────────────────────────────────────────────────────
        CRUD::addField(['name' => 'calorias',      'label' => 'Calorías',         'type' => 'number', 'attributes' => ['step' => '0.01'], 'wrapperAttributes' => ['class' => 'form-group col-md-3']]);
        CRUD::addField(['name' => 'carbohidratos', 'label' => 'Carbohidratos (g)','type' => 'number', 'attributes' => ['step' => '0.01'], 'wrapperAttributes' => ['class' => 'form-group col-md-3']]);
        CRUD::addField(['name' => 'proteinas',     'label' => 'Proteínas (g)',    'type' => 'number', 'attributes' => ['step' => '0.01'], 'wrapperAttributes' => ['class' => 'form-group col-md-3']]);
        CRUD::addField(['name' => 'grasas',        'label' => 'Grasas (g)',       'type' => 'number', 'attributes' => ['step' => '0.01'], 'wrapperAttributes' => ['class' => 'form-group col-md-3']]);

        // ── Tags ───────────────────────────────────────────────────────────
        CRUD::addField([
            'name'      => 'tags',
            'label'     => 'Tags',
            'type'      => 'select2_multiple_aura',
            'entity'    => 'tags',
            'attribute' => 'nombre',
            'model'     => \App\Models\Tag::class,
            'pivot'     => true,
            'options'   => fn ($q) => $q->where('type', 'individual')->get(),
        ]);
        CRUD::addField([
            'name'      => 'tags_business',
            'label'     => 'Tags de Business',
            'type'      => 'select2_multiple_aura',
            'entity'    => 'tags_business',
            'attribute' => 'nombre',
            'model'     => \App\Models\Tag::class,
            'pivot'     => true,
            'options'   => fn ($q) => $q->where('type', 'business')->get(),
        ]);

        // ── Hidden JSON arrays ─────────────────────────────────────────────
        CRUD::addField(['name' => 'array_ingredientes', 'type' => 'hidden']);
        CRUD::addField(['name' => 'array_resultados',   'type' => 'hidden']);
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Custom edit: pre-populate JSON arrays from DB
    // ─────────────────────────────────────────────────────────────────────────

    public function edit($id)
    {
        $this->crud->hasAccessOrFail('update');

        $id = $this->crud->getCurrentEntryId() ?? $id;

        $this->crud->registerFieldEvents();

        $entry = $this->crud->getEntryWithLocale($id);

        $ingredientes = $entry->recetaInstruccionReceta;
        $resultados   = $entry->recetaResultados;

        $array_ingredientes = [];
        foreach ($ingredientes as $i => $rir) {
            $rirm = $rir->rirm->first();
            $array_ingredientes[] = [
                'ingrediente'        => $rir->instruccion ? $rir->instruccion->ingrediente_id : $rir->subreceta_id,
                'ingrediente_nombre' => $rir->instruccion
                    ? ($rir->instruccion->ingrediente ? $rir->instruccion->ingrediente->nombre : 'No encontrado')
                    : ($rir->subreceta ? $rir->subreceta->titulo : ''),
                'cantidad'           => $rirm ? $rirm->cantidad : '',
                'instruccion'        => $rir->instruccion_id,
                'instruccion_nombre' => $rir->instruccion ? ($rir->instruccion->nombre ?? '') : '',
                'medida'             => $rirm ? $rirm->medida_id : null,
                'medida_nombre'      => $rirm && $rirm->medida ? $rirm->medida->nombre : '',
                'nota'               => $rir->nota ?? '',
                'es_ingrediente'     => $rir->subreceta_id ? false : true,
                'order'              => $i,
            ];
        }

        $array_resultados = [];
        foreach ($resultados as $resultado) {
            $array_resultados[] = [
                'medida'             => $resultado->medida_id,
                'medida_nombre'      => $resultado->medida ? $resultado->medida->nombre : '',
                'cantidad_resultado' => $resultado->cantidad,
                'active'             => $resultado->active,
            ];
        }

        // Set values on the model so Backpack picks them up when rendering hidden fields
        $entry->setAttribute('array_ingredientes', json_encode($array_ingredientes));
        $entry->setAttribute('array_resultados', json_encode($array_resultados));

        $this->crud->setOperationSetting('fields', $this->crud->getUpdateFields());

        $this->data['entry']      = $this->crud->entry = $entry;
        $this->data['crud']       = $this->crud;
        $this->data['saveAction'] = $this->crud->getSaveAction();
        $this->data['title']      = $this->crud->getTitle()
            ?? trans('backpack::crud.edit') . ' ' . $this->crud->entity_name;
        $this->data['id'] = $id;

        return view($this->crud->getEditView(), $this->data);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Store
    // ─────────────────────────────────────────────────────────────────────────

    public function store()
    {
        $this->crud->hasAccessOrFail('create');

        $request = $this->crud->validateRequest();

        // Read JSON arrays before Backpack strips them
        $ingredientes = (array) json_decode($request->get('array_ingredientes'));
        $resultados   = (array) json_decode($request->get('array_resultados'));

        $this->removeIngredienteFields($request);

        $this->crud->registerFieldEvents();
        $item = $this->crud->create($this->crud->getStrippedSaveRequest($request));
        $this->data['entry'] = $this->crud->entry = $item;

        foreach ($ingredientes as $ingrediente) {
            $rir = new RecetaInstruccionReceta();
            $rir->instruccion_id = $ingrediente->instruccion ?: null;
            $rir->receta_id      = $item->id;
            $rir->subreceta_id   = $ingrediente->es_ingrediente == false ? $ingrediente->ingrediente : null;
            $rir->nota           = $ingrediente->nota;
            $rir->save();

            $rirm = new RecetaInstruccionRecetaMedida();
            $rirm->cantidad        = $this->fractionToDecimal($ingrediente->cantidad);
            $rirm->rec_inst_rec_id = $rir->id;
            $rirm->medida_id       = $ingrediente->medida;
            $rirm->save();
        }

        $existe_porcion = false;
        foreach ($resultados as $resultado) {
            $existe_porcion = true;
            $rr = new RecetaResultado();
            $rr->receta_id = $item->id;
            $rr->medida_id = $resultado->medida;
            $rr->active    = $resultado->active;
            $rr->cantidad  = $this->fractionToDecimal($resultado->cantidad_resultado);
            $rr->save();
        }

        if (!$existe_porcion && $request->get('active')) {
            $rr = new RecetaResultado();
            $rr->receta_id = $item->id;
            $rr->medida_id = 10; // porción
            $rr->active    = 1;
            $rr->cantidad  = 2;
            $rr->save();
        }

        \Alert::success(trans('backpack::crud.insert_success'))->flash();
        $this->crud->setSaveAction();
        return $this->crud->performSaveAction($item->getKey());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Update
    // ─────────────────────────────────────────────────────────────────────────

    public function update()
    {
        $this->crud->hasAccessOrFail('update');

        $request = $this->crud->validateRequest();

        $recetaId     = $request->get($this->crud->model->getKeyName());
        $ingredientes = (array) json_decode($request->get('array_ingredientes'));
        $resultados   = (array) json_decode($request->get('array_resultados'));

        // Wipe existing ingredient relations before saving
        $receta = Receta::find($recetaId);
        foreach ($receta->recetaInstruccionReceta as $rir) {
            $rir->rirm()->delete();
        }
        $receta->recetaInstruccionReceta()->delete();
        $receta->recetaResultados()->delete();

        $this->removeIngredienteFields($request);

        $this->crud->registerFieldEvents();
        $item = $this->crud->update($recetaId, $this->crud->getStrippedSaveRequest($request));
        $this->data['entry'] = $this->crud->entry = $item;

        // Re-create ingredient relations
        foreach ($ingredientes as $ingrediente) {
            $rir = new RecetaInstruccionReceta();
            $rir->instruccion_id = $ingrediente->instruccion ?: null;
            $rir->receta_id      = $recetaId;
            $rir->subreceta_id   = $ingrediente->es_ingrediente == false ? $ingrediente->ingrediente : null;
            $rir->nota           = $ingrediente->nota;
            $rir->save();

            $rirm = new RecetaInstruccionRecetaMedida();
            $rirm->cantidad        = $this->fractionToDecimal($ingrediente->cantidad);
            $rirm->rec_inst_rec_id = $rir->id;
            $rirm->medida_id       = $ingrediente->medida;
            $rirm->updated_at      = null;
            $rirm->save();
        }

        $existe_porcion = false;
        foreach ($resultados as $resultado) {
            if ($resultado->medida == 10) {
                $existe_porcion = true;
            }
            $rr = new RecetaResultado();
            $rr->receta_id = $recetaId;
            $rr->medida_id = $resultado->medida;
            $rr->active    = $resultado->active;
            $rr->cantidad  = $this->fractionToDecimal($resultado->cantidad_resultado);
            $rr->save();
        }

        if (!$existe_porcion) {
            $rr = new RecetaResultado();
            $rr->receta_id = $recetaId;
            $rr->medida_id = 10;
            $rr->active    = 1;
            $rr->cantidad  = 2;
            $rr->save();
        }

        \Alert::success(trans('backpack::crud.update_success'))->flash();
        $this->crud->setSaveAction();
        return $this->crud->performSaveAction($item->getKey());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Details row
    // ─────────────────────────────────────────────────────────────────────────

    public function showDetailsRow($id)
    {
        $recipe = $this->crud->model->findOrFail($id);
        return view('admin.recipe_details', compact('recipe'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AJAX endpoints
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Returns instrucciones (preparation forms) for an ingredient.
     * Used by ingredientes_recetas_field.blade.php to populate the instruccion dropdown.
     */
    public function ingredienteMedida($ing)
    {
        $ingrediente = Ingrediente::find($ing);
        if ($ingrediente) {
            return $ingrediente->instrucciones ?: [];
        }
        return [];
    }

    /**
     * Returns compatible medidas for a sub-recipe (from its RecetaResultados).
     */
    public function recetaMedida($id)
    {
        $medidas = [];

        foreach (Medida::where('tipo_medida_id', 4)->get() as $m) {
            $medidas[] = $m;
        }

        $resultados = RecetaResultado::where('receta_id', $id)->get();
        foreach ($resultados as $resultado) {
            if ($resultado->medida && $resultado->medida->tipo_medida_id == 1) {
                foreach (Medida::where('tipo_medida_id', 1)->get() as $m) {
                    $medidas[] = $m;
                }
            } elseif ($resultado->medida) {
                $medidas[] = $resultado->medida;
            }
        }

        return $medidas;
    }

    /**
     * Returns compatible medidas for an instruccion + ingrediente combination.
     */
    public function instruccionMedida(Request $request)
    {
        $medidas = [];

        // Always include NC (tipo_medida_id = 4)
        foreach (Medida::where('tipo_medida_id', 4)->get() as $m) {
            $medidas[] = $m;
        }

        $instruccion = Instruccion::find($request->get('instruccion'));

        if ($instruccion && $instruccion->medida) {
            if ($instruccion->medida->tipo_medida_id == 1) {
                foreach (Medida::where('tipo_medida_id', 1)->get() as $m) {
                    $medidas[] = $m;
                }
            }
            $medidas[] = $instruccion->medida;
        }

        return $medidas;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function removeIngredienteFields(Request $request): void
    {
        foreach ([
            'array_ingredientes', 'array_resultados', 'ingrediente', 'instruccion',
            'medida', 'unidad_medida_id', 'cantidad', 'cantidad_resultado',
            'first_search', 'active_resultado', 'nota', 'receta-a-tip',
        ] as $field) {
            $request->request->remove($field);
        }
    }

    public function fractionToDecimal($fraction)
    {
        if (is_numeric($fraction)) {
            return $fraction;
        }

        preg_match('/^(?P<whole>\d+)?\s?((?P<numerator>\d+)\/(?P<denominator>\d+))?$/', $fraction, $m);

        $whole       = $m['whole']       ?? 0;
        $numerator   = $m['numerator']   ?? 0;
        $denominator = $m['denominator'] ?? 0;

        $decimal = (float) $whole;
        if ($numerator && $denominator) {
            $decimal += $numerator / $denominator;
        }

        return $decimal;
    }
}
