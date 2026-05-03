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

        CRUD::addField([
            'name' => 'tipo_id',
            'type' => 'hidden',
            'value' => 4,
        ]);

        CRUD::addField([
            'name' => 'recetas',
            'label' => 'Recetas',
            'type' => 'select2_multiple',
            'entity' => 'recetas',
            'attribute' => 'titulo',
            'model' => \App\Models\Receta::class,
            'pivot' => true,
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
        if (!$request->filled('tipo_id')) {
            $request->request->set('tipo_id', 4);
        }
        if (!$request->filled('svg')) {
            $request->request->set('svg', '');
        }
        if (!$request->filled('descripcion_recipes')) {
            $request->request->set('descripcion_recipes', '');
        }
        $payload = $this->parsePlanRecetaPayload($request->get('plan_receta_payload'));
        $request->request->remove('plan_receta_payload');

        $this->crud->registerFieldEvents();
        $item = $this->crud->create($this->crud->getStrippedSaveRequest($request));
        $this->persistPlanReceta($item->id, $payload);

        \Alert::success(trans('backpack::crud.insert_success'))->flash();
        $this->crud->setSaveAction();
        return $this->crud->performSaveAction($item->getKey());
    }

    public function update()
    {
        $this->crud->hasAccessOrFail('update');
        $request = $this->crud->validateRequest();
        if (!$request->filled('tipo_id')) {
            $request->request->set('tipo_id', 4);
        }
        if (!$request->filled('svg')) {
            $request->request->set('svg', '');
        }
        if (!$request->filled('descripcion_recipes')) {
            $request->request->set('descripcion_recipes', '');
        }

        $id = $request->get($this->crud->model->getKeyName());
        $payload = $this->parsePlanRecetaPayload($request->get('plan_receta_payload'));
        $request->request->remove('plan_receta_payload');

        $this->crud->registerFieldEvents();
        $item = $this->crud->update($id, $this->crud->getStrippedSaveRequest($request));
        $this->persistPlanReceta($id, $payload);

        \Alert::success(trans('backpack::crud.update_success'))->flash();
        $this->crud->setSaveAction();
        return $this->crud->performSaveAction($item->getKey());
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
}
