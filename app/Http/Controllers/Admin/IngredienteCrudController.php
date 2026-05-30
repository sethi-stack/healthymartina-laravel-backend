<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

// VALIDATION: change the requests to match your own file names if you need form validation
use App\Http\Requests\IngredienteRequest as StoreRequest;
use App\Http\Requests\IngredienteRequest as UpdateRequest;
use App\Models\Ingrediente;
use App\Models\Instruccion;
use App\Models\Medida;

/**
 * Class IngredienteCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class IngredienteCrudController extends CrudController
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
        CRUD::setModel(\App\Models\Ingrediente::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/Ingredientes');
        CRUD::setEntityNameStrings('ingrediente', 'ingredientes');
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        // Legacy-aligned columns.
        $this->crud->setColumns([
            [
                'name' => 'nombre',
                'type' => 'text',
                'label' => 'Nombre',
            ],
            [
                'name' => 'categoria',
                'type' => 'relationship',
                'label' => 'Categoría',
                'attribute' => 'nombre',
            ],
            [
                'name' => 'fdc_id',
                'type' => 'text',
                'label' => 'FDC ID',
            ],
            [
                'name' => 'aparece_en',
                'type' => 'model_function',
                'label' => 'Nº recetas',
                'function_name' => 'getApareceEn',
            ],
        ]);
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

        // Legacy-aligned edit/create form.
        $this->crud->removeAllFields();

        CRUD::addField([
            'name' => 'ingrediente_searcher',
            'type' => 'ingrediente_searcher',
            'label' => 'Buscador de ingredientes',
            'options' => Ingrediente::orderBy('nombre')->pluck('nombre', 'id')->toArray(),
        ]);

        CRUD::addField([
            'name'  => 'array_instrucciones',
            'type'  => 'hidden',
            'value' => '[]',
        ]);

        CRUD::addField([
            'name'  => 'nombre',
            'label' => 'Nombre del ingrediente',
            'type'  => 'text',
            'attributes' => ['required' => true],
        ]);

        CRUD::addField([
            'name'  => 'categoria',
            'label' => 'Categoria',
            'type'  => 'relationship',
            'attribute' => 'nombre',
            'entity' => 'categoria',
            'model' => \App\Models\Categoria::class,
            'wrapperAttributes' => ['class' => 'form-group col-md-4'],
        ]);

        CRUD::addField([
            'name'  => 'tipo_medida',
            'label' => 'Tipo Medida',
            'type'  => 'relationship',
            'attribute' => 'nombre',
            'entity' => 'tipo_medida',
            'model' => \App\Models\TipoMedida::class,
            'wrapperAttributes' => ['class' => 'form-group col-md-4'],
        ]);

        CRUD::addField([
            'name'  => 'usda',
            'label' => 'FDC API',
            'type'  => 'text',
            'hint'  => 'Ingresa el código de FDC (FDC ID).',
            'wrapperAttributes' => ['class' => 'form-group col-md-4'],
        ]);

        CRUD::addField([
            'name'  => 'fdc_search',
            'type'  => 'ingrediente_fdc_search',
            'label' => 'FDC Search',
            'wrapperAttributes' => ['class' => 'form-group col-md-12'],
        ]);

        CRUD::addField([
            'name'  => 'active',
            'label' => 'Activo',
            'type'  => 'boolean',
            'wrapperAttributes' => ['class' => 'form-group col-md-4'],
        ]);

        CRUD::addField([
            'name'  => 'instrucciones_inline',
            'type'  => 'ingrediente_instrucciones_inline',
            'label' => 'Nota de Preparación',
            'options_medidas' => Medida::orderBy('nombre')->pluck('nombre', 'id')->toArray(),
        ]);

        CRUD::addField([
            'name'  => 'forma_compra_title',
            'type'  => 'custom_html',
            'value' => '<hr><h3>Forma de compra en supermercado</h3>',
        ]);

        CRUD::addField([
            'name'  => 'forma_compra_help',
            'type'  => 'custom_html',
            'value' => '<p class="text-muted mb-3">Si el ingrediente es comprado directamente en gramos o mililitros, no colocar nada en esta equivalencia.</p>',
        ]);

        CRUD::addField([
            'name'  => 'cantidad_gramos',
            'label' => 'Cantidad en tipo de medida',
            'type'  => 'number',
            'attributes' => ['step' => '0.0001', 'min' => 0],
            'wrapperAttributes' => ['class' => 'form-group col-md-12'],
        ]);

        CRUD::addField([
            'name'  => 'forma_compra_equivalentes_title',
            'type'  => 'custom_html',
            'value' => '<h3 class="mt-3">Equivalentes a:</h3>',
        ]);

        CRUD::addField([
            'name'  => 'cantidad_forma_compra',
            'label' => 'Cantidad',
            'type'  => 'number',
            'attributes' => ['step' => '0.0001', 'min' => 0],
            'wrapperAttributes' => ['class' => 'form-group col-md-6'],
        ]);

        CRUD::addField([
            'name'  => 'forma_compra',
            'label' => 'Medida',
            'type'  => 'relationship',
            'attribute' => 'nombre',
            'entity' => 'forma_compra',
            'model' => \App\Models\Medida::class,
            'wrapperAttributes' => ['class' => 'form-group col-md-6'],
        ]);
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

        $entryId = $this->crud->getCurrentEntryId();
        if ($entryId) {
            $rows = Instruccion::with(['medida', 'equivalenciaMedida'])
                ->where('ingrediente_id', $entryId)
                ->get()
                ->map(function ($i) {
                    return [
                        'nota_preparacion' => $i->nota_preparacion ?? $i->nota ?? $i->nombre ?? '',
                        'sin_conversion' => (int) ($i->sin_conversion ?? 0),
                        'cantidad' => $i->cantidad ?? 0,
                        'equivalencia_gramos' => $i->equivalencia_gramos ?? null,
                        'medida_id' => $i->medida_id,
                        'medida_nombre' => optional($i->medida)->nombre,
                        'equivalencia_medida_id' => $i->equivalencia_medida_id,
                        'equivalencia_medida_nombre' => optional($i->equivalenciaMedida)->nombre,
                    ];
                })->values()->toJson();

            $this->crud->modifyField('array_instrucciones', ['value' => $rows]);
        }
    }

    /**
     * Store + persist inline instructions (legacy behavior).
     */
    public function store()
    {
        $this->crud->hasAccessOrFail('create');

        $request = $this->crud->validateRequest();

        $instructions = (array) json_decode((string) $request->get('array_instrucciones'), true);
        $request->request->remove('array_instrucciones');

        $this->crud->registerFieldEvents();
        $item = $this->crud->create($this->crud->getStrippedSaveRequest($request));
        $this->data['entry'] = $this->crud->entry = $item;

        $this->syncInstrucciones($item->id, $instructions);

        \Alert::success(trans('backpack::crud.insert_success'))->flash();
        $this->crud->setSaveAction();

        return $this->crud->performSaveAction($item->getKey());
    }

    /**
     * Update + persist inline instructions (legacy behavior).
     */
    public function update()
    {
        $this->crud->hasAccessOrFail('update');

        $request = $this->crud->validateRequest();

        $itemId = $request->get($this->crud->model->getKeyName());
        $instructions = (array) json_decode((string) $request->get('array_instrucciones'), true);
        $request->request->remove('array_instrucciones');

        $this->crud->registerFieldEvents();
        $item = $this->crud->update($itemId, $this->crud->getStrippedSaveRequest($request));
        $this->data['entry'] = $this->crud->entry = $item;

        $this->syncInstrucciones($itemId, $instructions);

        \Alert::success(trans('backpack::crud.update_success'))->flash();
        $this->crud->setSaveAction();

        return $this->crud->performSaveAction($item->getKey());
    }

    private function syncInstrucciones(int $ingredienteId, array $instructions): void
    {
        DB::transaction(function () use ($ingredienteId, $instructions) {
            // wipe and recreate (same behavior as legacy inline editors in this codebase)
            Instruccion::where('ingrediente_id', $ingredienteId)->delete();

            foreach ($instructions as $row) {
                $this->createInstruccionRow($ingredienteId, (array) $row);
            }
        });
    }

    private function createInstruccionRow(int $ingredienteId, array $row): void
    {
        $nota = (string) ($row['nota_preparacion'] ?? '');
        $sinConversion = (int) ($row['sin_conversion'] ?? 0);
        $cantidad = is_numeric($row['cantidad'] ?? null) ? (float) $row['cantidad'] : 0.0;
        $equivalenciaGramos = is_numeric($row['equivalencia_gramos'] ?? null) ? (float) $row['equivalencia_gramos'] : null;
        $medidaId = (int) ($row['medida_id'] ?? 0) ?: null;
        $equivalenciaMedidaId = (int) ($row['equivalencia_medida_id'] ?? 0) ?: null;

        // Try a few known schema variants (no information_schema access needed).
        $variants = [
            // prod legacy dump: `nombre` + `equivalencia_gramos` (+ optional equivalencia_medida_id)
            ['text' => 'nombre', 'qty' => 'equivalencia_gramos', 'with_equivalencia_medida_id' => true],
            ['text' => 'nombre', 'qty' => 'equivalencia_gramos', 'with_equivalencia_medida_id' => false],
            ['text' => 'nombre', 'qty' => 'cantidad', 'with_equivalencia_medida_id' => true],
            ['text' => 'nombre', 'qty' => 'cantidad', 'with_equivalencia_medida_id' => false],

            // newer variants we saw in code
            ['text' => 'nota_preparacion', 'qty' => 'equivalencia_gramos', 'with_equivalencia_medida_id' => true],
            ['text' => 'nota_preparacion', 'qty' => 'cantidad', 'with_equivalencia_medida_id' => true],
            ['text' => 'nota', 'qty' => 'equivalencia_gramos', 'with_equivalencia_medida_id' => true],
            ['text' => 'nota', 'qty' => 'cantidad', 'with_equivalencia_medida_id' => true],
        ];

        $lastException = null;

        foreach ($variants as $variant) {
            try {
                $instruccion = new Instruccion();
                $instruccion->ingrediente_id = $ingredienteId;
                $instruccion->sin_conversion = $sinConversion;
                $instruccion->medida_id = $medidaId;

                $instruccion->{$variant['text']} = $nota;
                $instruccion->{$variant['qty']} = $cantidad;

                // If the schema supports equivalencia_gramos too, store it (legacy uses it for grams conversion).
                if ($equivalenciaGramos !== null) {
                    try {
                        $instruccion->equivalencia_gramos = $equivalenciaGramos;
                    } catch (\Throwable $t) {
                        // ignore if column doesn't exist
                    }
                }

                if ($variant['with_equivalencia_medida_id']) {
                    $instruccion->equivalencia_medida_id = $equivalenciaMedidaId;
                }

                $instruccion->save();
                return;
            } catch (QueryException $e) {
                // Only keep trying when it's an "unknown column" mismatch.
                if (!str_contains($e->getMessage(), 'Unknown column')) {
                    throw $e;
                }
                $lastException = $e;
            }
        }

        // Nothing matched the schema; throw the last exception for debugging.
        if ($lastException) {
            throw $lastException;
        }
    }

    /**
     * AJAX: search foods by name in FDC and return select2-friendly results.
     */
    public function getFDCData(Request $request)
    {
        $query = (string) ($request->get('q') ?? $request->get('term') ?? '');
        if (trim($query) === '') {
            return response()->json(['results' => []]);
        }

        $apiKey = config('services.fdc.key') ?? env('FDC_API_KEY');
        if (!$apiKey) {
            return response()->json(['results' => [], 'error' => 'Missing FDC_API_KEY (set it in .env and clear config cache)'], 200);
        }

        $response = Http::timeout(15)->get('https://api.nal.usda.gov/fdc/v1/foods/search', [
            'api_key' => $apiKey,
            'query' => $query,
            'pageSize' => 25,
        ]);

        if (!$response->successful()) {
            return response()->json([
                'results' => [],
                'error' => 'FDC HTTP '.$response->status(),
            ], 200);
        }

        $foods = (array) ($response->json('foods') ?? []);
        $results = [];
        foreach ($foods as $food) {
            $fdcId = $food['fdcId'] ?? null;
            $description = $food['description'] ?? null;
            if (!$fdcId || !$description) {
                continue;
            }
            $results[] = ['id' => (string) $fdcId, 'text' => $description . ' (' . $fdcId . ')'];
        }

        return response()->json(['results' => $results]);
    }

    /**
     * AJAX: fetch one FDC food by id.
     */
    public function getFDCFood(Request $request)
    {
        $fdcId = (string) ($request->get('fdcId') ?? '');
        if ($fdcId === '') {
            return response()->json(['error' => 'Missing fdcId'], 422);
        }

        $apiKey = config('services.fdc.key') ?? env('FDC_API_KEY');
        if (!$apiKey) {
            return response()->json(['error' => 'Missing FDC_API_KEY'], 422);
        }

        $response = Http::timeout(15)->get('https://api.nal.usda.gov/fdc/v1/food/' . urlencode($fdcId), [
            'api_key' => $apiKey,
        ]);

        if (!$response->successful()) {
            return response()->json(['error' => 'FDC lookup failed'], 502);
        }

        return response()->json($response->json());
    }
}
