@php
    $field['value'] = old_empty_or_null($field['name'], '') ?? $field['value'] ?? [];
    $payload = is_array($field['value']) ? $field['value'] : (json_decode($field['value'], true) ?: []);
    $recipes = $field['recipes'] ?? [];
    $labels = config('constants.labels');
    $days = $labels['days'];
    $meals = $labels['meals'];
@endphp

@include('crud::fields.inc.wrapper_start')
    <label>{!! $field['label'] !!}</label>

    <input
        type="hidden"
        name="{{ $field['name'] }}"
        id="plan-receta-payload"
        value='@json($payload)'
    />

    <div id="plan-calendar-builder" data-recipes='@json($recipes)' data-days='@json($days)' data-meals='@json($meals)'>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th></th>
                    @foreach($days as $dayKey => $dayLabel)
                        <th>{{ $dayLabel }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

    <div class="modal fade" id="planMealModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Agregar comida</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <ul class="nav nav-tabs mb-3">
                        <li class="nav-item">
                            <a class="nav-link active" href="#" data-mealtype="main">Principal</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#" data-mealtype="side">Complemento</a>
                        </li>
                    </ul>
                    <div class="form-group">
                        <label>Receta</label>
                        <select class="form-control" id="planMealRecipeSelect"></select>
                    </div>
                    <div class="form-group">
                        <label>Porciones</label>
                        <input type="number" min="1" step="1" class="form-control" id="planMealServingInput" value="1" />
                    </div>
                    <div class="form-group form-check">
                        <input type="checkbox" class="form-check-input" id="planMealLeftoverInput" />
                        <label class="form-check-label" for="planMealLeftoverInput">Recalentado</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" id="planMealClearBtn">Limpiar</button>
                    <button type="button" class="btn btn-primary" id="planMealSaveBtn">Agregar a planner</button>
                </div>
            </div>
        </div>
    </div>

@include('crud::fields.inc.wrapper_end')

@push('crud_fields_styles')
<style>
    /* Ensure custom planner modal is above backdrop in Backpack admin layout */
    #planMealModal {
        z-index: 2005 !important;
        pointer-events: auto;
    }
    .modal-backdrop {
        z-index: 2000 !important;
    }
    #planMealModal .modal-dialog {
        z-index: 2006 !important;
        pointer-events: auto;
    }
    #planMealModal .modal-content {
        pointer-events: auto;
    }

    #plan-calendar-builder .cell-main, #plan-calendar-builder .cell-side {
        display: block;
        margin-bottom: 6px;
        font-size: 12px;
        line-height: 1.3;
    }
    #plan-calendar-builder .cell-main { color: #2f2f2f; }
    #plan-calendar-builder .cell-side { color: #6c757d; }
    #plan-calendar-builder .cell-action {
        display: inline-block;
        color: #007bff;
        cursor: pointer;
        font-weight: 600;
        font-size: 12px;
    }
</style>
@endpush

@push('crud_fields_scripts')
<script>
    (function () {
        const root = document.getElementById('plan-calendar-builder');
        const payloadInput = document.getElementById('plan-receta-payload');
        if (!root || !payloadInput) return;
        const modalEl = document.getElementById('planMealModal');
        if (modalEl && modalEl.parentElement !== document.body) {
            document.body.appendChild(modalEl);
        }

        const recipes = JSON.parse(root.dataset.recipes || '[]');
        const days = JSON.parse(root.dataset.days || '{}');
        const meals = JSON.parse(root.dataset.meals || '{}');
        const recipeMap = recipes.reduce((acc, item) => {
            acc[String(item.id)] = item.title;
            return acc;
        }, {});

        const defaults = {
            main_schedule: @json(config('constants.schedule')),
            sides_schedule: @json(config('constants.schedule')),
            main_servings: @json(config('constants.main_servings')),
            sides_servings: @json(config('constants.sides_servings')),
            main_leftovers: @json(config('constants.leftovers')),
            sides_leftovers: @json(config('constants.leftovers')),
            labels: @json(config('constants.labels')),
        };

        let state = JSON.parse(JSON.stringify(defaults));
        let context = { dayKey: null, mealKey: null, mealType: 'main' };

        try {
            const incoming = JSON.parse(payloadInput.value || '{}');
            if (incoming && typeof incoming === 'object') {
                Object.keys(defaults).forEach((key) => {
                    const value = incoming[key];
                    if (!value) return;
                    state[key] = typeof value === 'string' ? JSON.parse(value) : value;
                });
            }
        } catch (_e) {}

        const select = document.getElementById('planMealRecipeSelect');
        const servingInput = document.getElementById('planMealServingInput');
        const leftoverInput = document.getElementById('planMealLeftoverInput');
        const saveBtn = document.getElementById('planMealSaveBtn');
        const clearBtn = document.getElementById('planMealClearBtn');
        const tabLinks = document.querySelectorAll('#planMealModal [data-mealtype]');

        function syncInput() {
            payloadInput.value = JSON.stringify(state);
        }

        function buildRecipeSelect() {
            select.innerHTML = '<option value="">Selecciona receta</option>';
            recipes.forEach((item) => {
                const option = document.createElement('option');
                option.value = item.id;
                option.textContent = item.title;
                select.appendChild(option);
            });
        }

        function getCellLabel(dayKey, mealKey, mealType) {
            const scheduleKey = mealType === 'main' ? 'main_schedule' : 'sides_schedule';
            const servingsKey = mealType === 'main' ? 'main_servings' : 'sides_servings';
            const leftoversKey = mealType === 'main' ? 'main_leftovers' : 'sides_leftovers';
            const recipeId = state[scheduleKey]?.[dayKey]?.[mealKey];
            if (!recipeId) return '';
            const title = recipeMap[String(recipeId)] || `Receta #${recipeId}`;
            const serving = state[servingsKey]?.[dayKey]?.[mealKey] || '';
            const leftover = state[leftoversKey]?.[dayKey]?.[mealKey] ? ' · Recalentado' : '';
            const prefix = mealType === 'main' ? 'P' : 'C';
            return `${prefix}: ${title}${serving ? ` (${serving})` : ''}${leftover}`;
        }

        function renderTable() {
            const tbody = root.querySelector('tbody');
            tbody.innerHTML = '';

            Object.keys(meals).forEach((mealKey) => {
                const tr = document.createElement('tr');
                const th = document.createElement('th');
                th.textContent = meals[mealKey];
                tr.appendChild(th);

                Object.keys(days).forEach((dayKey) => {
                    const td = document.createElement('td');

                    const main = document.createElement('span');
                    main.className = 'cell-main';
                    main.textContent = getCellLabel(dayKey, mealKey, 'main') || 'P: —';
                    td.appendChild(main);

                    const side = document.createElement('span');
                    side.className = 'cell-side';
                    side.textContent = getCellLabel(dayKey, mealKey, 'side') || 'C: —';
                    td.appendChild(side);

                    const add = document.createElement('span');
                    add.className = 'cell-action';
                    add.textContent = 'ADD';
                    add.addEventListener('click', function () {
                        context = { dayKey, mealKey, mealType: 'main' };
                        setActiveTab('main');
                        hydrateModal();
                        window.jQuery('#planMealModal').modal({
                            backdrop: true,
                            keyboard: true,
                            focus: true
                        }).modal('show');
                    });
                    td.appendChild(add);

                    tr.appendChild(td);
                });

                tbody.appendChild(tr);
            });

            syncInput();
        }

        function setActiveTab(mealType) {
            context.mealType = mealType;
            tabLinks.forEach((link) => {
                link.classList.toggle('active', link.dataset.mealtype === mealType);
            });
        }

        function hydrateModal() {
            const { dayKey, mealKey, mealType } = context;
            const scheduleKey = mealType === 'main' ? 'main_schedule' : 'sides_schedule';
            const servingsKey = mealType === 'main' ? 'main_servings' : 'sides_servings';
            const leftoversKey = mealType === 'main' ? 'main_leftovers' : 'sides_leftovers';

            select.value = state[scheduleKey]?.[dayKey]?.[mealKey] || '';
            servingInput.value = state[servingsKey]?.[dayKey]?.[mealKey] || 1;
            leftoverInput.checked = Boolean(state[leftoversKey]?.[dayKey]?.[mealKey]);
        }

        tabLinks.forEach((link) => {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                setActiveTab(link.dataset.mealtype);
                hydrateModal();
            });
        });

        saveBtn.addEventListener('click', function () {
            const { dayKey, mealKey, mealType } = context;
            const scheduleKey = mealType === 'main' ? 'main_schedule' : 'sides_schedule';
            const servingsKey = mealType === 'main' ? 'main_servings' : 'sides_servings';
            const leftoversKey = mealType === 'main' ? 'main_leftovers' : 'sides_leftovers';

            const recipeId = select.value ? Number(select.value) : null;
            state[scheduleKey][dayKey][mealKey] = recipeId;
            state[servingsKey][dayKey][mealKey] = recipeId ? Number(servingInput.value || 1) : null;
            state[leftoversKey][dayKey][mealKey] = recipeId ? Boolean(leftoverInput.checked) : null;

            renderTable();
            window.jQuery('#planMealModal').modal('hide');
        });

        clearBtn.addEventListener('click', function () {
            select.value = '';
            servingInput.value = 1;
            leftoverInput.checked = false;
        });

        buildRecipeSelect();
        renderTable();
    })();
</script>
@endpush
