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

## Latest QA Update - 2026-05-04
Source: client retest after recent deploy.

## Client Retest Snapshot (After First Test - Verbatim)
HEALTHY MARTINA
TESTING software upgrade

- When I open a recipe, sub-recipes are not enabled yet.:white_check_mark:
- Ingredients and tips are not showing in the recipes.:white_check_mark:
- It is not possible to add recipes to the calendar from the main recipe library.:white_check_mark:
  - :bangbang:• *BUT* it doesn’t let me select de calendar that I want to add the recipe
  - :bangbang:recalentado it’s not showing in the picture (transparent image)
- From the recipe view, the “add to calendar” button is not working.:white_check_mark:
  - :bangbang:• *BUT* it doesn’t let me select de calendar that I want to add the recipe
  - :bangbang:recalentado it’s not showing in the picture (transparent image)
- From the recipe view, the “export recipe” button is also not working. :x:not working yet
- The “add comment” button at the bottom is not working.:x:not working yet
- Filters work correctly when combining tags with number of ingredients and time.:white_check_mark:
- The issue appears when combining tags with “include ingredients” or “exclude ingredients”: results drop to zero instead of filtering correctly.:white_check_mark:
- When a filter is applied, if you open a recipe and then go back to the recipe list, the filter resets and has to be set up again.:white_check_mark:
- I wasn’t able to fully test how the nutrition filters work when combined, because as soon as I open a recipe and go back, the filter resets. This makes it impossible to check multiple recipes in a row to see if the filter is working properly.:white_check_mark:
- The bookmark button is not working. When I click on it, nothing happens.:white_check_mark:
- When adding a recipe to the calendar, it seems to be slower than the original version. Since this update is intended to improve speed, I just wanted to flag this in case it’s something that can be optimized or reviewed.:white_check_mark:
- When I modify the number of servings for a recipe in the calendar, it affects other days where that same recipe was previously selected in the same meal slot :x:this is still the same, it needs to be independent in order to work with l ft overs (for example, breakfast). When editing one day (like Monday), the system shows all the days where that recipe was applied (e.g., Wednesday, Friday, Saturday). However, if I only confirm the change for Monday and don’t reselect or validate the recipe again for the other days, those entries get deleted. This means changes are not applied independently per day, but instead behave as a grouped action across multiple days.
- The same issue happens when using the leftovers button. If I don’t reselect or validate the recipe again for all the days where it was originally applied, those entries disappear from the calendar.:x:same here
- The portions button is not working. There are two options (“servings” and “portions”), and the portions button does not respond when clicked. :white_check_mark:working now but not showing the number of portions on the calendar (in the picture)
- When adding a side or making the recipe as a leftover, the system is very slow. At first it seems like it’s not working, but it eventually loads — just with a noticeable delay. This action can be applied both individually and in bulk.:white_check_mark:done :x:*BUT* not showing the picture in shadow (lighter)
- When I click the “view recipe details” button, it leads to a 404 error (page not found).:white_check_mark:
- From the three-dot menu on a recipe in the calendar, it’s not possible to mark it as leftovers, and selecting “view details” leads to a 404 error (page not found). Also, if I choose delete and that same recipe exists on other days in the same meal slot (e.g., dinner), it deletes all instances instead of just that one.:white_check_mark:
- I can drag the recipe, but when I try to drop it somewhere else, it goes back to its original place. :white_check_mark: but it looks like it’s dragging the recipe below to (at the end it just drops the one that it supposed to drop
- If I manually add a recipe to a slot (for example, adding a breakfast on Monday without using the full-week edit/block view), and that same slot already had recipes on other days (like Saturday), those existing entries get deleted if I don’t revalidate or confirm them in the full-week view.:white_check_mark:
- When I click on the nutrition insights of the calendar, nothing happens — the feature doesn’t respond yet.:white_check_mark: it shows the window :x: BUT not the details
- The calendar is not exporting the cover or the recipes, and the current export format does not match the one we plan to use. :white_check_mark: PDF export theme/format now matches target :x: It still doesn’t have images file:///Users/cristinaarvizu/Downloads/NUEVO%20(3).pdf
  - :bangbang:it didn’t work with 9 recipe just with 3
  - *This feature needs to be completely frictionless since it’s the one they’ll be using the most.*
- It is not possible to add ingredients, validate existing items in the list, or export/send the grocery list by email. I:white_check_mark: can add an ingredient but :x:not the measure form (cup, pices etc) :x:doesn’t export or email list
- The plans section is not ready yet, but this can likely be addressed later.:white_check_mark:

### Confirmed working
- Sub-recipes enabled in recipe view.
- Ingredients/tips rendering fixed.
- Add-to-calendar from main library works.
- Add-to-calendar from recipe detail works.
- Filter combos and filter persistence are resolved.
- Bookmark flow works.
- Drag/drop now persists (visual drag-ghost issue still open).
- View recipe details 404 fixed.
- 3-dot calendar menu actions (leftover/view/delete scope) fixed.
- Manual add no longer wipes other-day slot entries.
- Plans section now accessible and completed for current scope.

### Reopened / still failing
- None currently in this section after latest validation pass.

### New bugs found in this pass
- `NEW P1.7` Add-to-calendar flow does not allow choosing target calendar from library/detail entry points.
- `NEW P3.2` Calendar picker modal title alignment is slightly shifted right (UI polish, low priority).

## Client List Mapping (Solved vs Open)
- `1` Sub-recipes not enabled → `P2.1` → `SOLVED` (`DONE`, 2026-05-05).
- `2` Ingredients/tips missing → `P1.1` → `SOLVED` (`DONE`, 2026-04-29).
- `3` Main library add-to-calendar unavailable → `P1.2` → `SOLVED` (`DONE`, 2026-04-29).
- `4` Recipe view add-to-calendar broken → `P0.1` → `SOLVED` (`DONE`, 2026-04-29).
- `5` Recipe view export broken → `P0.1a` → `SOLVED` (`DONE`, 2026-05-04).
- `6` Add comment broken → `P0.1b` → `SOLVED` (`DONE`, 2026-05-05).
- `7` Tags + ingredient-count/time works → Behavior note, no bug ticket needed.
- `8` Tags + include/exclude returns zero unexpectedly → `P1.4` → `SOLVED` (`DONE`, 2026-04-30).
- `9` Filter resets on recipe open/back → `P1.5` → `SOLVED` (`DONE`, 2026-04-30).
- `10` Nutrition-filter validation blocked by reset → `P1.6`/`P1.5` blockers `SOLVED`; full nutrition-mix QA still recommended.
- `11` Bookmark button not working → `P1.3` → `SOLVED` (`DONE`, 2026-04-29; UX follow-up 2026-04-30).
- `12` Calendar add feels slower than previous → `P2.2` → `SOLVED` (`DONE`, 2026-05-05).
- `13` Servings grouped destructive behavior → `P0.3` → `SOLVED` (`DONE`, 2026-05-04).
- `14` Leftovers grouped destructive behavior → `P0.3-L` → `SOLVED` (`DONE`, 2026-05-04).
- `15` Portions button not responding → `P2.4` → `SOLVED` (`DONE`, 2026-04-30).
- `16` Side/leftovers latency → `P2.3` → `SOLVED` (`DONE`, 2026-05-05).
- `17` View recipe details 404 → `P0.2` → `SOLVED` (`DONE`, 2026-04-29).
- `18` Calendar 3-dot menu leftovers/view details/delete issues → leftovers/view details/delete scopes `SOLVED` (`P1.6`/`P0.2`/`P0.3`).
- `19` Drag/drop reverts → `P0.4` → `PARTIAL` (`DONE WITH OPEN UI BUG`, 2026-04-29).
- `20` Manual add deletes other-day slot entries → `P0.3` → `SOLVED` (`DONE`, 2026-04-29).
- `21` Nutrition insights unresponsive → `SOLVED` (`P2.5 DONE`, 2026-05-04).
- `22` Calendar export incomplete/mismatch → `P2.6` → `SOLVED` (`DONE`, 2026-05-05).
- `23` Grocery list core actions unavailable → `P0.5` → `SOLVED` (`DONE`, 2026-05-05).
- `24` Plans section not ready → `P3.1` → `SOLVED` (`DONE`, 2026-05-05).
- Add-to-calendar target calendar selector missing → `P1.7` → `NEW`.
- Leftover/reheated visual overlay missing → `P1.8` → `SOLVED` (`DONE`, 2026-05-05).
- Portions value not shown on calendar cards → `P2.7` → `SOLVED` (`DONE`, 2026-05-05).
- Drag ghost preview artifact while dragging → `P2.8` → `SOLVED` (`DONE`, 2026-05-05).

## Goal
Track initial QA feedback, prioritize by criticality, and execute fixes one at a time with testing checkpoints between each implementation.

## Priority Legend
- `P0` Critical bug or blocker (core flow broken, data loss, major 404, key CTA not working)
- `P1` High bug / missing expected feature (important flow degraded but not fully blocked)
- `P2` Enhancement / performance / polish (works but needs optimization or completion)
- `P3` Future scope (known not ready yet)

## Prioritized Backlog

### P0 - Critical (fix first)
1. **Recipe view buttons not working** - `DONE` (2026-05-05)
   - Add to calendar button now works.
   - Export recipe fixed (`P0.1a`, 2026-05-04).
   - Add comment flow/menu fixes validated (`P0.1b`).

2. **Recipe details navigation broken** - `DONE` (2026-04-29)
   - “View recipe details” leads to 404.
   - Same 404 from 3-dot calendar menu.

3. **Calendar destructive grouped behavior / data integrity** - `DONE` (2026-05-04)
   - Editing servings on one day affects other days in same meal slot.
   - If not revalidated in full-week flow, entries on other days get deleted.
   - Same issue with leftovers.
   - Deleting one instance from menu deletes all matching instances across days/slot.
   - Manually adding one slot can delete existing entries in same slot on other days.

4. **Drag and drop cannot complete move** - `DONE WITH OPEN UI BUG` (2026-04-29)
   - Item returns to original position after drop.
   - Open bug: drag preview shows extra height at bottom while dragging.

5. **Grocery list core actions unavailable** - `REOPENED` (2026-05-04)
   - Cannot add ingredients.
   - Cannot validate existing list items.
   - Cannot export/send grocery list by email.
   - Add ingredient works, but measurement/unit parity still missing.

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

7. **Add-to-calendar target calendar selection missing** - `NEW` (2026-05-04)
   - From recipe library/detail add flow, user cannot choose target calendar.

8. **Leftover/reheated visual overlay missing** - `NEW` (2026-05-04)
   - Lighter/transparent leftover visual state not rendered in card image.

### P2 - Enhancement / Performance / Partial readiness
1. **Sub-recipes not enabled yet** - `DONE` (2026-05-05)
   - UX enablement pass started in recipe detail (explicit clickable sub-recipe links).
2. **Calendar add recipe feels slower than previous version** (performance regression suspicion).
3. **Side/leftover actions feel slow before eventual load** (latency optimization).
4. **Portions button not responding** (servings/portions toggle behavior needs review).
5. **Nutrition insights interaction/details incomplete** - `DONE` (2026-05-04)
   - Modal/details payload rendering fixed.
6. **Calendar export format/content incomplete** - `PARTIAL / HIGH RISK` (updated 2026-05-05)
   - Missing cover and recipes in export.
   - Export theme/format now matches target (`DONE`, 2026-05-05).
   - Fails on larger recipe counts (reported: 9 fails, 3 works).
7. **Portions value not shown on calendar card** - `NEW` (2026-05-04)
8. **Drag preview ghost artifact** - `NEW` (2026-05-04)
   - Dragging one recipe visually appears to include another recipe below.

### P3 - Future
1. **Plans section not ready yet** - `DONE` (2026-05-05)
   - React route + initial page scaffolding enabled (`/planes`) with list/copy/pdf actions.

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
- 2026-04-30: `P2.5` completed.
  - Implemented nutrition insights click interaction in calendar nutrition row.
  - Added day nutrition details modal and wired per-day click to open it.
  - Modal reflects current view mode (`statistics` amounts / `macros` percentages).
- 2026-05-03: Plans + sub-recipes resumed.
  - Added React Plans route `/planes`, new `Planes` page, and API client integration for:
    - `GET /plans`
    - `GET /plans/{id}`
    - `POST /plans/{id}/copy`
    - `GET /plans/{id}/pdf`
- 2026-05-05: Calendar card polish fixes completed.
  - `P1.8` leftover/reheated visual state now renders correctly with lighter overlay.
  - `P2.8` drag ghost artifact resolved by dedicated drag preview element.
  - `P2.7` serving count now shown inline in calendar card title row (separate count element).
- 2026-05-05: `P0.5` completed.
  - Manual grocery items now support `unidad_medida` parity in API create/update/read.
  - Lista add/edit/delete now refreshes only the affected category with in-card loading indicator.
  - Lista success alerts removed for cleaner UX; backend email errors now surfaced directly.
- 2026-05-05: Validation pass closed remaining items.
  - `P0.1b` add-comment flow confirmed working.
  - `P2.2` calendar add performance marked done for current scope.
  - `P2.3` side/leftover latency marked done for current scope.
  - `P2.6` calendar export parity/reliability marked done.
  - Sub-recipe UX pass started in Recipe Detail ingredients:
    - Render sub-recipe ingredients as explicit clickable links (`sub-url`) instead of relying only on injected HTML.
- 2026-05-04: Client retest ingested and tracker refreshed.
  - Reopened critical regressions: recipe export, add comment, calendar servings independence, leftovers independence.
  - Reopened nutrition details, export parity/reliability, and grocery list completion gaps.
  - Added new bugs: add-to-calendar target selection, leftover overlay visual, portions display, drag ghost preview artifact.
- 2026-05-04: `P0.1a` completed.
  - Recipe export switched to legacy Advanced/Bold template path by default.
  - Dompdf remote assets enabled.
  - Recipe image embedded as base64 fallback for stable PDF rendering.
- 2026-05-04: `P0.1b` implementation update.
  - Comment kebab menu migrated to reusable `hm-menu` structure for correct icon/label spacing.
  - Delete option now hidden for non-owned comments (`is_owned_by_current_user === true` only).
  - Pending QA retest on add-comment end-to-end behavior.
- 2026-05-04: `P0.3` / `P0.3-L` completed.
  - Update meal modal now defaults to day-scoped edit (no implicit multi-day preselection).
  - Backend update no longer prunes unchecked days unless explicitly requested via `prune_unselected=true`.
  - QA retest passed: editing one day no longer deletes/changes other same-slot days.
- 2026-05-04: `P2.5` completed.
  - Nutrition row/modal now renders returned nutrient detail items even when values are zero.
  - Fixed empty-details modal state in affected flow.

## Active Next Item
- `P2.1` **Sub-recipes full enablement** - `DONE` (2026-05-05)

### Deferred UI polish
- `P3.2` Calendar picker modal title alignment (left offset mismatch) - `DEFERRED` (2026-05-04)

## Notes
- This document is intentionally execution-oriented so we can track status quickly.
- As we complete each item, we should add a status marker (`TODO`, `IN PROGRESS`, `DONE`, `NEEDS RETEST`) and timestamp.
