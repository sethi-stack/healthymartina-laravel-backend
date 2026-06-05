<?php

namespace App\Http\Controllers\Admin;

use App\Models\PlanReceta;
use App\Models\Receta;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

// VALIDATION: change the requests to match your own file names if you need form validation
use App\Http\Requests\PlanRequest as StoreRequest;
use App\Http\Requests\PlanRequest as UpdateRequest;

/**
 * Class PlanCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class PlanCrudController extends CrudController
{
    private const PLAN_TYPE_INVISIBLE = 1;
    private const PLAN_TYPE_VISIBLE = 4;

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
        CRUD::setModel(\App\Models\Plan::class);
        CRUD::setRoute(config("backpack.base.route_prefix") . "/plan");
        CRUD::setEntityNameStrings("plan", "planes");
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::setFromDb(); // set columns from db columns.
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
        CRUD::setFromDb(); // keep legacy metadata fields from DB.
        CRUD::removeField('best');
        CRUD::removeField('slug');
        CRUD::removeField('tipo_id');
        CRUD::removeField('guia');
        CRUD::removeField('editado');
        CRUD::removeField('manual');
        CRUD::removeField('introduccion');
        CRUD::removeField('icono');
        CRUD::removeField('descripcion_recipes');
        CRUD::removeField('recetas');
        CRUD::removeField('svg');
        CRUD::removeField('duracion');
        CRUD::removeField('descripcion');

        CRUD::addField([
            'name' => 'invisible_display',
            'label' => 'Invisible',
            'type' => 'checkbox',
            'default' => $this->isInvisibleEntry() ? 1 : 0,
            'value' => $this->isInvisibleEntry() ? 1 : 0,
            'wrapperAttributes' => [
                'class' => 'form-group col-md-4',
            ],
        ]);

        CRUD::addField([
            'name' => 'tipo_id',
            'type' => 'hidden',
            'value' => $this->isInvisibleEntry() ? self::PLAN_TYPE_INVISIBLE : self::PLAN_TYPE_VISIBLE,
        ]);

        CRUD::addField([
            'name' => 'introduccion',
            'type' => 'hidden',
            'value' => '',
        ]);

        CRUD::addField([
            'name' => 'descripcion_recipes',
            'type' => 'hidden',
            'value' => '',
        ]);

        CRUD::addField([
            'name' => 'svg',
            'type' => 'hidden',
            'value' => '',
        ]);

        CRUD::addField([
            'name' => 'editado',
            'type' => 'hidden',
            'value' => 0,
        ]);

        CRUD::addField([
            'name' => 'manual',
            'type' => 'hidden',
            'value' => 0,
        ]);

        CRUD::addField([
            'name' => 'duracion',
            'label' => 'Dias',
            'type' => 'number',
            'attributes' => [
                'min' => 1,
                'step' => 1,
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-4',
            ],
        ]);

        CRUD::addField([
            'name' => 'descripcion',
            'label' => 'Descripción',
            'type' => 'summernote',
        ]);

        $recipes = Receta::where('active', 1)
            ->orderBy('titulo')
            ->get(['id', 'titulo'])
            ->map(fn ($recipe) => ['id' => $recipe->id, 'title' => $recipe->titulo])
            ->values()
            ->all();

        CRUD::addField([
            'name' => 'plan_receta_payload',
            'label' => 'Calendario',
            'type' => 'plan_calendar_builder',
            'recipes' => $recipes,
            'value' => $this->getPlanRecetaPayload(),
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
    }

    public function store()
    {
        $this->crud->hasAccessOrFail('create');
        $request = $this->crud->validateRequest();
        $request->request->set('tipo_id', $request->boolean('invisible_display')
            ? self::PLAN_TYPE_INVISIBLE
            : self::PLAN_TYPE_VISIBLE);
        $this->applyLegacyFieldDefaults($request);
        $payload = $this->parsePlanRecetaPayload($request->get('plan_receta_payload'));
        $request->request->remove('plan_receta_payload');

        $this->crud->registerFieldEvents();
        $item = $this->crud->create($this->crud->getStrippedSaveRequest($request));
        $this->persistPlanReceta($item->id, $payload);
        $item->recetas()->sync($this->extractRecipeIdsFromPayload($payload));

        \Alert::success(trans('backpack::crud.insert_success'))->flash();
        $this->crud->setSaveAction();
        return $this->crud->performSaveAction($item->getKey());
    }

    public function update()
    {
        $this->crud->hasAccessOrFail('update');
        $request = $this->crud->validateRequest();
        $request->request->set('tipo_id', $request->boolean('invisible_display')
            ? self::PLAN_TYPE_INVISIBLE
            : self::PLAN_TYPE_VISIBLE);
        $this->applyLegacyFieldDefaults($request);

        $id = $request->get($this->crud->model->getKeyName());
        $payload = $this->parsePlanRecetaPayload($request->get('plan_receta_payload'));
        $request->request->remove('plan_receta_payload');

        $this->crud->registerFieldEvents();
        $item = $this->crud->update($id, $this->crud->getStrippedSaveRequest($request));
        $this->persistPlanReceta($id, $payload);
        $item->recetas()->sync($this->extractRecipeIdsFromPayload($payload));

        \Alert::success(trans('backpack::crud.update_success'))->flash();
        $this->crud->setSaveAction();
        return $this->crud->performSaveAction($item->getKey());
    }

    private function applyLegacyFieldDefaults($request): void
    {
        if (!$request->filled('svg')) {
            $request->request->set('svg', '');
        }

        if (!$request->filled('descripcion_recipes')) {
            $request->request->set('descripcion_recipes', '');
        }

        if (!$request->filled('introduccion')) {
            $request->request->set('introduccion', '');
        }

        if (!$request->filled('editado')) {
            $request->request->set('editado', 0);
        }

        if (!$request->filled('manual')) {
            $request->request->set('manual', 0);
        }
    }

    private function parsePlanRecetaPayload($rawPayload): array
    {
        if (!$rawPayload) {
            return [];
        }
        if (is_array($rawPayload)) {
            return $rawPayload;
        }
        $decoded = json_decode($rawPayload, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function getPlanRecetaPayload(): array
    {
        $entry = $this->crud->getCurrentEntry();
        if (!$entry || !$entry->plan_receta) {
            return [];
        }
        return [
            'main_schedule' => $entry->plan_receta->main_schedule,
            'sides_schedule' => $entry->plan_receta->sides_schedule,
            'main_servings' => $entry->plan_receta->main_servings,
            'sides_servings' => $entry->plan_receta->sides_servings,
            'main_leftovers' => $entry->plan_receta->main_leftovers,
            'sides_leftovers' => $entry->plan_receta->sides_leftovers,
            'labels' => $entry->plan_receta->labels,
        ];
    }

    private function persistPlanReceta(int $planId, array $payload): void
    {
        $mainSchedule = $payload['main_schedule'] ?? config('constants.schedule');
        $sidesSchedule = $payload['sides_schedule'] ?? config('constants.schedule');
        $mainServings = $payload['main_servings'] ?? config('constants.main_servings');
        $sidesServings = $payload['sides_servings'] ?? config('constants.sides_servings');
        $mainLeftovers = $payload['main_leftovers'] ?? config('constants.leftovers');
        $sidesLeftovers = $payload['sides_leftovers'] ?? config('constants.leftovers');
        $labels = $payload['labels'] ?? config('constants.labels');

        PlanReceta::updateOrCreate(
            ['plan_id' => $planId],
            [
                'main_schedule' => json_encode($mainSchedule),
                'sides_schedule' => json_encode($sidesSchedule),
                'main_servings' => json_encode($mainServings),
                'sides_servings' => json_encode($sidesServings),
                'main_leftovers' => json_encode($mainLeftovers),
                'sides_leftovers' => json_encode($sidesLeftovers),
                'labels' => json_encode($labels),
            ]
        );
    }

    private function isInvisibleEntry(): bool
    {
        $entry = $this->crud->getCurrentEntry();

        return $entry && (int) $entry->tipo_id === self::PLAN_TYPE_INVISIBLE;
    }

    private function extractRecipeIdsFromPayload(array $payload): array
    {
        $recipeIds = [];

        foreach (['main_schedule', 'sides_schedule'] as $scheduleKey) {
            $schedule = $payload[$scheduleKey] ?? [];

            if (!is_array($schedule)) {
                continue;
            }

            foreach ($schedule as $dayMeals) {
                if (!is_array($dayMeals)) {
                    continue;
                }

                foreach ($dayMeals as $recipeId) {
                    $normalizedId = (int) $recipeId;
                    if ($normalizedId > 0) {
                        $recipeIds[] = $normalizedId;
                    }
                }
            }
        }

        return array_values(array_unique($recipeIds));
    }
}
