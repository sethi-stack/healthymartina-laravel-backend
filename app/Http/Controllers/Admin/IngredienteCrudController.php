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
use Illuminate\Support\Str;

/**
 * Class IngredienteCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class IngredienteCrudController extends CrudController
{
    private const ENABLE_FDC_SIN_CONVERSION_AUTOFILL = false;
    private const LEGACY_SIN_CONVERSION_MEDIDA_ID = 7;
    private const LEGACY_SIN_CONVERSION_CANTIDAD = 1.0;
    private const LEGACY_SIN_CONVERSION_EQUIVALENCIA_GRAMOS = 1.0;

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
            'name' => 'fdc_raw',
            'type' => 'hidden',
        ]);

        CRUD::addField([
            'name' => 'fdc_name',
            'type' => 'hidden',
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
        $nota = trim((string) ($row['nota_preparacion'] ?? ''));
        $sinConversion = (int) ($row['sin_conversion'] ?? 0);
        $cantidad = is_numeric($row['cantidad'] ?? null) ? (float) $row['cantidad'] : null;
        $equivalenciaGramos = is_numeric($row['equivalencia_gramos'] ?? null) ? (float) $row['equivalencia_gramos'] : null;
        $medidaId = (int) ($row['medida_id'] ?? 0) ?: null;
        $equivalenciaMedidaId = (int) ($row['equivalencia_medida_id'] ?? 0) ?: null;

        if (self::ENABLE_FDC_SIN_CONVERSION_AUTOFILL && $sinConversion === 1) {
            $fdcDefaults = $this->deriveInstructionDefaultsFromFdc($ingredienteId);
            if ($fdcDefaults) {
                $nota = $nota !== '' ? $nota : ($fdcDefaults['nota_preparacion'] ?? 'NA');
                $cantidad = $cantidad ?? ($fdcDefaults['cantidad'] ?? null);
                $equivalenciaGramos = $equivalenciaGramos ?? ($fdcDefaults['equivalencia_gramos'] ?? null);
                $medidaId = $medidaId ?: ($fdcDefaults['medida_id'] ?? null);
            }
        } elseif ($sinConversion === 1) {
            $cantidad = self::LEGACY_SIN_CONVERSION_CANTIDAD;
            $equivalenciaGramos = self::LEGACY_SIN_CONVERSION_EQUIVALENCIA_GRAMOS;
            $medidaId = self::LEGACY_SIN_CONVERSION_MEDIDA_ID;
        }

        $cantidad = $cantidad ?? 0.0;
        $nota = $nota !== '' ? $nota : 'NA';

        // Try a few known schema variants (no information_schema access needed).
        $variants = [
            // Prefer schemas where `cantidad` is the row amount and `equivalencia_gramos` is the conversion value.
            ['text' => 'nombre', 'qty' => 'cantidad', 'with_equivalencia_medida_id' => true],
            ['text' => 'nombre', 'qty' => 'cantidad', 'with_equivalencia_medida_id' => false],
            ['text' => 'nombre', 'qty' => 'equivalencia_gramos', 'with_equivalencia_medida_id' => true],
            ['text' => 'nombre', 'qty' => 'equivalencia_gramos', 'with_equivalencia_medida_id' => false],

            ['text' => 'nota_preparacion', 'qty' => 'cantidad', 'with_equivalencia_medida_id' => true],
            ['text' => 'nota_preparacion', 'qty' => 'equivalencia_gramos', 'with_equivalencia_medida_id' => true],
            ['text' => 'nota', 'qty' => 'cantidad', 'with_equivalencia_medida_id' => true],
            ['text' => 'nota', 'qty' => 'equivalencia_gramos', 'with_equivalencia_medida_id' => true],
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

    private function deriveInstructionDefaultsFromFdc(int $ingredienteId): ?array
    {
        $ingrediente = Ingrediente::find($ingredienteId);
        if (!$ingrediente || empty($ingrediente->fdc_raw)) {
            return null;
        }

        $fdc = json_decode((string) $ingrediente->fdc_raw, true);
        if (!is_array($fdc)) {
            return null;
        }

        $quantity = null;
        $unit = null;
        $equivalence = null;

        $servingSize = isset($fdc['servingSize']) && is_numeric($fdc['servingSize'])
            ? (float) $fdc['servingSize']
            : null;
        $servingUnit = trim((string) ($fdc['servingSizeUnit'] ?? ''));

        if ($servingSize && $servingUnit !== '') {
            $quantity = $servingSize;
            $unit = $servingUnit;
            $equivalence = $this->resolveEquivalentWeightFromUnit($servingSize, $servingUnit);
        }

        if ((!$quantity || !$unit) && !empty($fdc['foodPortions']) && is_array($fdc['foodPortions'])) {
            foreach ($fdc['foodPortions'] as $portion) {
                $portionAmount = isset($portion['amount']) && is_numeric($portion['amount'])
                    ? (float) $portion['amount']
                    : null;
                $portionUnit = trim((string) ($portion['measureUnit']['abbreviation'] ?? $portion['measureUnit']['name'] ?? ''));
                $portionGramWeight = isset($portion['gramWeight']) && is_numeric($portion['gramWeight'])
                    ? (float) $portion['gramWeight']
                    : null;

                if ($portionAmount && $portionUnit !== '') {
                    $quantity = $portionAmount;
                    $unit = $portionUnit;
                    $equivalence = $portionGramWeight;
                    break;
                }

                if (!$quantity && $portionGramWeight) {
                    $quantity = $portionGramWeight;
                    $unit = 'g';
                    $equivalence = $portionGramWeight;
                    break;
                }
            }
        }

        if (!$quantity) {
            return null;
        }

        if (!$unit) {
            $unit = 'g';
        }

        if ($equivalence === null) {
            $equivalence = $this->resolveEquivalentWeightFromUnit($quantity, $unit);
        }

        return [
            'nota_preparacion' => 'NA',
            'cantidad' => $quantity,
            'medida_id' => $this->resolveMedidaIdFromUnit($unit),
            'equivalencia_gramos' => $equivalence,
        ];
    }

    private function resolveEquivalentWeightFromUnit(float $quantity, string $unit): ?float
    {
        $normalizedUnit = $this->normalizeUnitLabel($unit);
        if (in_array($normalizedUnit, ['g', 'gram', 'grams', 'gr', 'gramo', 'gramos', 'gm'], true)) {
            return $quantity;
        }

        if (in_array($normalizedUnit, ['ml', 'milliliter', 'milliliters', 'mililitro', 'mililitros'], true)) {
            return $quantity;
        }

        return null;
    }

    private function resolveMedidaIdFromUnit(?string $unit): ?int
    {
        $unit = $this->normalizeUnitLabel((string) $unit);
        if ($unit === '') {
            return null;
        }

        $aliases = [
            'g' => ['g', 'gr', 'gm', 'gram', 'grams', 'gramo', 'gramos'],
            'ml' => ['ml', 'milliliter', 'milliliters', 'mililitro', 'mililitros'],
            'kg' => ['kg', 'kilogram', 'kilograms', 'kilo', 'kilos', 'kilogramo', 'kilogramos'],
            'oz' => ['oz', 'ounce', 'ounces', 'onza', 'onzas'],
            'lb' => ['lb', 'lbs', 'pound', 'pounds', 'libra', 'libras'],
            'tz' => ['cup', 'cups', 'taza', 'tazas', 'tz'],
            'cda' => ['tbsp', 'tablespoon', 'tablespoons', 'cda', 'cdas', 'cucharada', 'cucharadas'],
            'cdta' => ['tsp', 'teaspoon', 'teaspoons', 'cdta', 'cdtas', 'cucharadita', 'cucharaditas'],
            'pieza' => ['pieza', 'piezas', 'piece', 'pieces', 'unidad', 'unidades', 'unit', 'units'],
        ];

        $wantedAliases = [$unit];
        foreach ($aliases as $group) {
            if (in_array($unit, $group, true)) {
                $wantedAliases = $group;
                break;
            }
        }

        $medidas = Medida::all();
        foreach ($medidas as $medida) {
            $candidates = [
                $medida->nombre ?? '',
                $medida->abreviatura ?? '',
                $medida->abreviatura_plural ?? '',
                $medida->nombre_english ?? '',
            ];

            foreach ($candidates as $candidate) {
                $normalizedCandidate = $this->normalizeUnitLabel((string) $candidate);
                if ($normalizedCandidate !== '' && in_array($normalizedCandidate, $wantedAliases, true)) {
                    return (int) $medida->id;
                }
            }
        }

        return null;
    }

    private function normalizeUnitLabel(string $value): string
    {
        return Str::of($value)
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9]+/', '')
            ->toString();
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
