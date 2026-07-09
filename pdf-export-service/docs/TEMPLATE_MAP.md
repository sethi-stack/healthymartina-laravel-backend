# PDF Export Template Map

This document maps the current Node PDF export files and what each one is responsible for.

## Runtime Entry Points

| File | Purpose |
| --- | --- |
| `src/server.js` | Express API, job queue, async lifecycle, HMAC verification, HTML to PDF rendering, and file download/status endpoints. |
| `src/templates/legacy/index.js` | Main legacy document composer used by the runtime. It decides which sections to render and assembles the final HTML document. |

## Data Shaping

| File | Purpose |
| --- | --- |
| `src/templates/legacy/model.js` | Normalizes the Laravel payload into the legacy model used by the live renderer. Builds cover, weekly plan, nutrition summary, shopping list, and recipe pages. |
| `src/templates/legacy/boldBundleTemplate.js` | Alternate bundle composer for the legacy bold format. It reshapes a plain model into the same section structure, but is not the primary runtime entry in `server.js`. |

## Section Renderers

| File | Purpose |
| --- | --- |
| `src/templates/legacy/components/cover.js` | Renders the cover page. |
| `src/templates/legacy/components/weeklyPlan.js` | Renders the calendar / weekly plan page. |
| `src/templates/legacy/components/nutrition.js` | Renders the nutrition summary section for the calendar export. |
| `src/templates/legacy/components/lista.js` | Renders the shopping list section. |
| `src/templates/legacy/components/recipes.js` | Renders recipe detail pages. This is the main recipe page template used by the Node service. |
| `src/templates/legacy/components/footer.js` | Renders the PDF footer. |
| `src/templates/legacy/components/utils.js` | Shared escaping and formatting helpers for the legacy components. |

## Styling

| File | Purpose |
| --- | --- |
| `src/templates/legacy/styles.js` | Wraps all section styles into the final HTML document. |

## Reference Assets

| File | Purpose |
| --- | --- |
| `src/templates/legacy/REFERENCE.md` | Notes on the reverse-engineered template source of truth. |
| `src/templates/legacy/REFERENCE_MAP.json` | File-to-section reference mapping for the reverse-engineered template. |
| `src/templates/legacy/assets/reverse_engineered_healthy_martina_pdf.html` | Source reference used to reconstruct the legacy PDF layout. |

## Current Behavior Notes

- The live Node service uses `src/templates/legacy/index.js` as the main document composer.
- Recipe detail pages are rendered by `src/templates/legacy/components/recipes.js`.
- Calendar nutrition summary is rendered separately by `src/templates/legacy/components/nutrition.js`.
- If we later remove duplicated recipe template behavior, this is the file map to use as the cleanup guide.
