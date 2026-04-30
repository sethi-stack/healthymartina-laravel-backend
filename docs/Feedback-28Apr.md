# Feedback - 28 Apr

## Client Issues List (Primary Reference)
Use this section as the quickest reference to client-reported issues.

1. When opening a recipe, sub-recipes are not enabled yet.
2. Ingredients and tips are not showing in recipes.
3. Cannot add recipes to calendar from main recipe library.
4. From recipe view, “add to calendar” button not working.
5. From recipe view, “export recipe” button not working.
6. “Add comment” button at bottom not working.
7. Tags + number of ingredients + time works.
8. Tags + include ingredients OR tags + exclude ingredients drops to zero results unexpectedly.
9. If a filter is applied, opening a recipe and returning resets filters.
10. Nutrition filter combinations are hard to validate because filters reset on back navigation.
11. Bookmark button not working.
12. Adding recipe to calendar feels slower than original version.
13. Editing servings on one day affects/deletes same-slot entries on other days unless revalidated.
14. Same grouped-delete behavior happens with leftovers.
15. Portions button (“servings/portions”) is not responding.
16. Adding side or leftovers is very slow before eventual load.
17. “View recipe details” leads to 404.
18. Calendar 3-dot menu issues: cannot mark leftovers, “view details” 404, delete removes all same-slot instances across days.
19. Drag can start, but drop reverts to original place.
20. Manually adding one slot can delete same-slot entries on other days if not revalidated in full-week view.
21. Nutrition insights click does nothing.
22. Calendar export missing cover/recipes and format mismatch.
23. Grocery list core actions unavailable (add/validate/export/email).
24. Plans section not ready yet (later scope).

## Client List Mapping (Solved vs Open)
- `1` Sub-recipes not enabled → `P2.1` → `OPEN` (known incomplete feature).
- `2` Ingredients/tips missing → `P1.1` → `SOLVED` (`DONE`, 2026-04-29).
- `3` Main library add-to-calendar unavailable → `P1.2` → `SOLVED` (`DONE`, 2026-04-29).
- `4` Recipe view add-to-calendar broken → `P0.1` → `SOLVED` (`DONE`, 2026-04-29).
- `5` Recipe view export broken → `P0.1` → `SOLVED` (`DONE`, 2026-04-29).
- `6` Add comment broken → `P0.1` → `SOLVED` (`DONE`, 2026-04-29).
- `7` Tags + ingredient-count/time works → Behavior note, no bug ticket needed.
- `8` Tags + include/exclude returns zero unexpectedly → `P1.4` → `SOLVED` (`DONE`, 2026-04-30).
- `9` Filter resets on recipe open/back → `P1.5` → `SOLVED` (`DONE`, 2026-04-30).
- `10` Nutrition-filter validation blocked by reset → `P1.6`/`P1.5` blockers `SOLVED`; full nutrition-mix QA still recommended.
- `11` Bookmark button not working → `P1.3` → `SOLVED` (`DONE`, 2026-04-29; UX follow-up 2026-04-30).
- `12` Calendar add feels slower than previous → `P2.2` → `OPEN`.
- `13` Servings grouped destructive behavior → `P0.3` → `SOLVED` (`DONE`, 2026-04-29; needs QA confidence).
- `14` Leftovers grouped destructive behavior → `P0.3` → `SOLVED` (`DONE`, 2026-04-29; needs QA confidence).
- `15` Portions button not responding → `P2.4` → `SOLVED` (`DONE`, 2026-04-30).
- `16` Side/leftovers latency → `P2.3` → `OPEN`.
- `17` View recipe details 404 → `P0.2` → `SOLVED` (`DONE`, 2026-04-29).
- `18` Calendar 3-dot menu leftovers/view details/delete issues → leftovers/view details/delete scopes `SOLVED` (`P1.6`/`P0.2`/`P0.3`).
- `19` Drag/drop reverts → `P0.4` → `PARTIAL` (`DONE WITH OPEN UI BUG`, 2026-04-29).
- `20` Manual add deletes other-day slot entries → `P0.3` → `SOLVED` (`DONE`, 2026-04-29).
- `21` Nutrition insights unresponsive → `P2.5` → `OPEN`.
- `22` Calendar export incomplete/mismatch → `P2.6` → `OPEN`.
- `23` Grocery list core actions unavailable → `P0.5` → `NEEDS RETEST`.
- `24` Plans section not ready → `P3.1` → `DEFERRED`.

## Goal
Track initial QA feedback, prioritize by criticality, and execute fixes one at a time with testing checkpoints between each implementation.

## Priority Legend
- `P0` Critical bug or blocker (core flow broken, data loss, major 404, key CTA not working)
- `P1` High bug / missing expected feature (important flow degraded but not fully blocked)
- `P2` Enhancement / performance / polish (works but needs optimization or completion)
- `P3` Future scope (known not ready yet)

## Prioritized Backlog

### P0 - Critical (fix first)
1. **Recipe view buttons not working** - `DONE` (2026-04-29)
   - Add to calendar button does not work.
   - Export recipe button does not work.
   - Add comment button does not work.

2. **Recipe details navigation broken** - `DONE` (2026-04-29)
   - “View recipe details” leads to 404.
   - Same 404 from 3-dot calendar menu.

3. **Calendar destructive grouped behavior / data integrity** - `DONE` (2026-04-29)
   - Editing servings on one day affects other days in same meal slot.
   - If not revalidated in full-week flow, entries on other days get deleted.
   - Same issue with leftovers.
   - Deleting one instance from menu deletes all matching instances across days/slot.
   - Manually adding one slot can delete existing entries in same slot on other days.

4. **Drag and drop cannot complete move** - `DONE WITH OPEN UI BUG` (2026-04-29)
   - Item returns to original position after drop.
   - Open bug: drag preview shows extra height at bottom while dragging.

5. **Grocery list core actions unavailable** - `NEEDS RETEST` (2026-04-29)
   - Cannot add ingredients.
   - Cannot validate existing list items.
   - Cannot export/send grocery list by email.

### P1 - High
1. **Recipe content missing in detail** - `DONE` (2026-04-29)
   - Ingredients, instructions, and tips rendering restored in recipes.
   - Fixed recipe detail tab/content style collisions caused by global `.options` selectors.

2. **Main recipe library calendar action unavailable** - `DONE` (2026-04-29)
   - Cannot add recipes to calendar from main library.

3. **Bookmark action not working** - `DONE` (2026-04-29)
   - Fixed frontend bookmark action to use `/recipes/bookmarks` endpoint instead of sending an unused query flag to `/recipes`.
   - Added per-recipe bookmark toggle in recipe card 3-dot menu (`Guardar/Quitar marcador`) wired to `POST /recipes/{id}/bookmark`.

4. **Filter logic bug (specific combination)** - `DONE` (2026-04-30)
   - Tags + include ingredients OR tags + exclude ingredients returns zero unexpectedly.

5. **Filter state is lost on back navigation** - `DONE` (2026-04-30)
   - Open recipe and return to list resets filters.
   - Also blocks reliable nutrition-filter validation across multiple recipes.

6. **Calendar recipe menu limitation** - `DONE` (2026-04-30)
   - From 3-dot menu, cannot mark recipe as leftovers.

### P2 - Enhancement / Performance / Partial readiness
1. **Sub-recipes not enabled yet** (feature incomplete / readiness gap).
2. **Calendar add recipe feels slower than previous version** (performance regression suspicion).
3. **Side/leftover actions feel slow before eventual load** (latency optimization).
4. **Portions button not responding** (servings/portions toggle behavior needs review).
5. **Nutrition insights click does nothing** (feature not responsive yet).
6. **Calendar export format/content incomplete**
   - Missing cover and recipes in export.
   - Export format not yet matching target format.

### P3 - Future
1. **Plans section not ready yet** (explicitly deferred).

## Execution Plan (One-at-a-time with testing)

### Phase 1 - Stabilize blockers (`P0`)
1. Fix recipe view CTA actions (add to calendar, export, add comment).
2. Fix recipe details routes (all entry points, including 3-dot menu).
3. Fix calendar grouped update/delete logic to enforce per-day instance isolation.
4. Fix drag-and-drop persistence/mutation update.
5. Fix grocery list core actions (add, validate, export/email).

Testing checkpoint after **each** item:
- Implement one item.
- Hand over for your test.
- Continue only after your confirmation.

### Phase 2 - Core UX correctness (`P1`)
1. Restore ingredients/tips rendering in recipe detail.
2. Re-enable add-to-calendar from main library.
3. Fix bookmark persistence/action.
4. Correct filter combination logic for tags + include/exclude ingredients.
5. Persist filter state across recipe open/back navigation.
6. Re-check nutrition filters once state persistence is fixed.
7. Enable leftovers from calendar 3-dot menu.

Testing checkpoint after **each** item.

### Phase 3 - Performance and incomplete features (`P2`)
1. Profile and optimize calendar add recipe / side / leftovers latency.
2. Fix portions toggle behavior.
3. Enable nutrition insights interaction or gate as “coming soon”.
4. Complete calendar export (cover + recipes + target format parity).
5. Review sub-recipe enablement scope and rollout.

Testing checkpoint after **each** item.

### Phase 4 - Deferred (`P3`)
1. Plan section readiness after higher-priority stabilization is complete.

## Implementation Order For Next Steps
Start with:
1. Recipe details routes (`P0.2`) - `NEXT`
2. Calendar grouped update/delete logic (`P0.3`)
3. Drag and drop persistence (`P0.4`)
4. Grocery list core actions (`P0.5`)

## Progress Log
- 2026-04-29: `P0.1` completed.
  - Recipe detail add-to-calendar wired to legacy `AddMealModal` flow.
  - Recipe export wired to `/recipes/{id}/pdf`.
  - Comment action/popup flow restored and popup UI spacing fixed.
- 2026-04-29: `P1.2` completed.
  - Main recipe library add-to-calendar now opens legacy `AddMealModal` with selected recipe preloaded.
  - Stored calendar selection now validated against current calendar list, with safe fallback + persistence.
- 2026-04-29: `P0.2` completed.
  - Added robust recipe detail routing fallback by ID (`/receta-id/:id`) when slug is unavailable.
  - Calendar menu “Ver receta” now opens by slug when present, otherwise by ID.
  - Add-meal “Ver detalles de receta” link now uses same slug/ID fallback.
- 2026-04-29: `P0.3` completed.
  - Fixed destructive grouped calendar behavior in update flow.
  - In edit popup, day uncheck now removes recipe from unchecked days as expected.
  - Prevented stale selected calendar mismatch when switching calendars.
  - Reduced calendar edit over-fetch by tightening query invalidation and cache updates.
- 2026-04-29: `P0.4` functional completion.
  - Drag/drop now persists and supports main+side movement.
  - Known remaining UI bug tracked: drag preview extra bottom height.
- 2026-04-29: `P0.5` started.
  - Fixed lista frontend API routes to match backend endpoints.
  - Wired lista email send with required `lista_ingredients` payload.
- 2026-04-29: `P0.5` implementation pass completed (pending QA retest).
  - Fixed lista toggle endpoint + payload (`ingrediente_id`, `categoria_id`, `ingrediente_type`).
  - Added optimistic update for ingredient check/uncheck to reduce perceived lag.
  - Fixed custom ingredient add/update/delete routes and calendar ID wiring.
  - Merged `custom_items` into category rendering so manual ingredients show under selected category.
  - Connected lista export/email payload handling and improved mutation/refetch behavior.
- 2026-04-29: Lista/Calendar UX follow-up completed.
  - Added loading overlays for API wait states (post-initial-load waits).
  - Removed duplicate loader behavior on initial page load.
  - Fixed lista checkbox alignment and text wrapping by reusing shared checkbox pattern.
  - Scoped auth-only checkbox styles to prevent global leakage into Lista layout.
  - Improved Lista header readability (`ingredientes` counter contrast).
- 2026-04-29: Commits for this phase:
  - `2239df0` - Fix lista/calendar loading UX and checkbox alignment.
  - `fbc0f30` - Update calendar cell behavior/styles and add agent guidance.
- 2026-04-29: `P1.1` completed.
  - Restored recipe detail content rendering for ingredients/instructions/tips.
  - Fixed instruction line-merge formatting from split backend lines.
  - Scoped conflicting global `.options` styles to stop cross-page CSS leakage into recipe detail tabs.
  - Commit: `0f967be` (react-front-app).
- 2026-04-30: Recetario filter/bookmark UX follow-up completed.
  - Added reusable `IconActionButton` and migrated Recetario action buttons to `react-icons`.
  - Added filter bookmarks modal flow (save / apply / delete) aligned with legacy behavior.
  - Fixed active/hover visual states and removed bookmark active highlight by request (only filter remains stateful).
  - Fixed Recetario options row overflow/line bleed via scoped spacing/overflow corrections.
  - Commit: `07ca766` (react-front-app).
- 2026-04-30: `P1.4` completed.
  - Aligned advanced filter ingredient SQL constraints with legacy semantics in `RecipeFilterService` (`ingrediente_id` in include/exclude nested conditions) to address zero-result regressions on tags + include/exclude combinations.
  - QA retest passed on target combinations.
  - Commit: `17b7b03` (laravel-backend-app).
- 2026-04-30: `P1.5` completed.
  - Persisted Recetario filters in URL + `localStorage` so filter state survives back navigation and page reloads until explicit clear (`Sin filtros`).
  - Added legacy query fallback hydration for compatibility with old filter links.
  - Commit: `18fde96` (react-front-app).
- 2026-04-30: `P1.6` completed.
  - Implemented calendar 3-dot menu leftover toggle from `CalendarCell` for main and side recipes.
  - Wired toggle mutation to calendar update endpoint and cache invalidation.
  - QA retest passed.
  - Commit: `050d7db` (react-front-app).
- 2026-04-30: `P2.4` completed.
  - Added API endpoint `POST /api/v1/calendars/{id}/racion` to persist ración updates per meal/day.
  - Added React client method and wired update popup `Ración` trigger to this endpoint.
  - Added clickable `Ración` control next to servings text in calendar update modal for main/side recipes.
  - Updated calendar cell UX: clicking side recipe now opens `Complemento` tab first.
  - Fixed calendar card height regression while preserving click behavior.

## Active Next Item
- `P2.2` **Calendar add recipe feels slower than previous version** - `IN PROGRESS` (2026-04-30)
  - Start profiling add-recipe flow and reduce perceived/actual latency (request path, query invalidation, and loading UX).

## Notes
- This document is intentionally execution-oriented so we can track status quickly.
- As we complete each item, we should add a status marker (`TODO`, `IN PROGRESS`, `DONE`, `NEEDS RETEST`) and timestamp.
