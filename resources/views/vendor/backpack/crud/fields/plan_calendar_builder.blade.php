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
                        <input type="text" class="form-control" id="planMealRecipeSearch" placeholder="Buscar receta..." autocomplete="off" />
                        <input type="hidden" id="planMealRecipeSelect" />
                        <div id="planMealRecipeResults" class="plan-meal-search-results"></div>
                    </div>
                    <div class="form-group">
                        <label>Días</label>
                        <div id="planMealDaysSelector" class="plan-meal-days"></div>
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
@basset('https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css')
@basset('https://cdn.jsdelivr.net/npm/select2-bootstrap-theme@0.1.0-beta.10/dist/select2-bootstrap.min.css')
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
    .plan-meal-days {
        display: flex;
        flex-wrap: wrap;
        gap: 8px 12px;
    }
    .plan-meal-days__option {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin: 0;
        font-weight: 400;
    }
    .plan-meal-search-results {
        margin-top: 8px;
        border: 1px solid #ced4da;
        border-radius: .25rem;
        background: #fff;
        max-height: 240px;
        overflow-y: auto;
        display: none;
    }
    .plan-meal-search-results.is-open {
        display: block;
    }
    .plan-meal-search-result {
        padding: 8px 12px;
        cursor: pointer;
        border-bottom: 1px solid #f1f3f5;
        font-size: 14px;
        line-height: 1.35;
    }
    .plan-meal-search-result:last-child {
        border-bottom: 0;
    }
    .plan-meal-search-result:hover,
    .plan-meal-search-result.is-selected {
        background: #f8f9fa;
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
        const normalizedRecipes = recipes.map((item) => ({
            id: Number(item.id),
            text: item.title || item.titulo || item.name || `Receta #${item.id}`,
        }));
        const recipeMap = normalizedRecipes.reduce((acc, item) => {
            acc[String(item.id)] = item.text;
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
        const recipeSearch = document.getElementById('planMealRecipeSearch');
        const recipeResults = document.getElementById('planMealRecipeResults');
        const daysSelector = document.getElementById('planMealDaysSelector');
        const servingInput = document.getElementById('planMealServingInput');
        const leftoverInput = document.getElementById('planMealLeftoverInput');
        const saveBtn = document.getElementById('planMealSaveBtn');
        const clearBtn = document.getElementById('planMealClearBtn');
        const tabLinks = document.querySelectorAll('#planMealModal [data-mealtype]');
        let selectedDays = [];

        function syncInput() {
            payloadInput.value = JSON.stringify(state);
        }

        function buildRecipeSelect() {
            renderRecipeResults(normalizedRecipes);
        }

        function handleRecipeChange() {
            const recipeId = select.value ? Number(select.value) : null;
            if (recipeId) {
                const matchingDays = findMatchingDays(recipeId, context.mealKey, context.mealType);
                selectedDays = matchingDays.length ? matchingDays : [context.dayKey];
            } else {
                selectedDays = [context.dayKey];
            }
            renderDaysSelector();
        }

        function getMealStateKey(baseKey, mealType) {
            return mealType === 'main' ? `main_${baseKey}` : `sides_${baseKey}`;
        }

        function findMatchingDays(recipeId, mealKey, mealType) {
            const scheduleKey = getMealStateKey('schedule', mealType);
            return Object.keys(days).filter((dayKey) => {
                return Number(state[scheduleKey]?.[dayKey]?.[mealKey] || 0) === Number(recipeId);
            });
        }

        function getRecipeText(recipeId) {
            return recipeMap[String(recipeId)] || '';
        }

        function renderRecipeResults(items) {
            recipeResults.innerHTML = '';

            if (!items.length) {
                const empty = document.createElement('div');
                empty.className = 'plan-meal-search-result';
                empty.textContent = 'No se encontraron recetas';
                recipeResults.appendChild(empty);
                recipeResults.classList.add('is-open');
                return;
            }

            items.forEach((item) => {
                const option = document.createElement('div');
                option.className = 'plan-meal-search-result';
                if (String(select.value || '') === String(item.id)) {
                    option.classList.add('is-selected');
                }
                option.textContent = item.text;
                option.addEventListener('click', function () {
                    select.value = String(item.id);
                    recipeSearch.value = item.text;
                    recipeResults.classList.remove('is-open');
                    handleRecipeChange();
                });
                recipeResults.appendChild(option);
            });

            recipeResults.classList.add('is-open');
        }

        function filterRecipes(query) {
            const normalizedQuery = String(query || '').trim().toLowerCase();
            if (!normalizedQuery) {
                return normalizedRecipes;
            }

            return normalizedRecipes.filter((item) =>
                item.text.toLowerCase().includes(normalizedQuery)
            );
        }

        function renderDaysSelector() {
            daysSelector.innerHTML = '';
            Object.entries(days).forEach(([dayKey, dayLabel]) => {
                const label = document.createElement('label');
                label.className = 'plan-meal-days__option';

                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.value = dayKey;
                checkbox.checked = selectedDays.includes(dayKey);
                checkbox.addEventListener('change', function () {
                    if (checkbox.checked) {
                        if (!selectedDays.includes(dayKey)) {
                            selectedDays = [...selectedDays, dayKey];
                        }
                    } else {
                        selectedDays = selectedDays.filter((value) => value !== dayKey);
                    }
                });

                const span = document.createElement('span');
                span.textContent = dayLabel;

                label.appendChild(checkbox);
                label.appendChild(span);
                daysSelector.appendChild(label);
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
            const scheduleKey = getMealStateKey('schedule', mealType);
            const servingsKey = getMealStateKey('servings', mealType);
            const leftoversKey = getMealStateKey('leftovers', mealType);

            const recipeId = state[scheduleKey]?.[dayKey]?.[mealKey] || '';
            select.value = recipeId;
            recipeSearch.value = recipeId ? getRecipeText(recipeId) : '';
            recipeResults.classList.remove('is-open');
            servingInput.value = state[servingsKey]?.[dayKey]?.[mealKey] || 1;
            leftoverInput.checked = Boolean(state[leftoversKey]?.[dayKey]?.[mealKey]);
            selectedDays = recipeId ? findMatchingDays(recipeId, mealKey, mealType) : [dayKey];
            if (!selectedDays.length) {
                selectedDays = [dayKey];
            }
            renderDaysSelector();
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
            const scheduleKey = getMealStateKey('schedule', mealType);
            const servingsKey = getMealStateKey('servings', mealType);
            const leftoversKey = getMealStateKey('leftovers', mealType);

            const recipeId = select.value ? Number(select.value) : null;
            const targetDays = selectedDays.length ? selectedDays : [dayKey];
            const allDays = Object.keys(days);

            if (recipeId) {
                targetDays.forEach((selectedDayKey) => {
                    state[scheduleKey][selectedDayKey][mealKey] = recipeId;
                    state[servingsKey][selectedDayKey][mealKey] = Number(servingInput.value || 1);
                    state[leftoversKey][selectedDayKey][mealKey] = Boolean(leftoverInput.checked);
                });
            } else {
                state[scheduleKey][dayKey][mealKey] = null;
                state[servingsKey][dayKey][mealKey] = null;
                state[leftoversKey][dayKey][mealKey] = null;
            }

            if (recipeId) {
                allDays.forEach((existingDayKey) => {
                    if (targetDays.includes(existingDayKey)) {
                        return;
                    }
                    if (Number(state[scheduleKey]?.[existingDayKey]?.[mealKey] || 0) === recipeId) {
                        state[scheduleKey][existingDayKey][mealKey] = null;
                        state[servingsKey][existingDayKey][mealKey] = null;
                        state[leftoversKey][existingDayKey][mealKey] = null;
                    }
                });
            }

            renderTable();
            window.jQuery('#planMealModal').modal('hide');
        });

        clearBtn.addEventListener('click', function () {
            select.value = '';
            recipeSearch.value = '';
            servingInput.value = 1;
            leftoverInput.checked = false;
            selectedDays = [context.dayKey];
            renderDaysSelector();
            renderRecipeResults(normalizedRecipes);
        });

        recipeSearch.addEventListener('focus', function () {
            renderRecipeResults(filterRecipes(recipeSearch.value));
        });

        recipeSearch.addEventListener('input', function () {
            select.value = '';
            renderRecipeResults(filterRecipes(recipeSearch.value));
        });

        document.addEventListener('click', function (event) {
            const withinSearch = recipeSearch.contains(event.target) || recipeResults.contains(event.target);
            const withinModal = modalEl && modalEl.contains(event.target);
            if (!withinSearch && !withinModal) {
                recipeResults.classList.remove('is-open');
            }
        });

        buildRecipeSelect();
        renderTable();
    })();
</script>
@endpush
