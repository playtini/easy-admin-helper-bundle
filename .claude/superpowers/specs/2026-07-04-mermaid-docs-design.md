# Mermaid diagram rendering in docs — Design

**Date:** 2026-07-04
**Status:** Approved

## Problem

Markdown docs served at `/admin/doc/{name}` render ` ```mermaid ` fenced code
blocks as raw, un-rendered code (styled as a code block) instead of as diagrams.

Current pipeline:

```
doc/*.md
  → FrontmatterParser::parseFileToHtml()
  → (new Parsedown())->text($body)
  → HTML string
  → item.html.twig  {{ content|raw }}
```

Parsedown renders a ` ```mermaid ` fence as:

```html
<pre><code class="language-mermaid">stateDiagram-v2 ... </code></pre>
```

with its inner text HTML-escaped (`-->` → `--&gt;`, `<br>` → `&lt;br&gt;`).

## Goal

` ```mermaid ` fenced blocks in `doc/*.md` render as diagrams on
`/admin/doc/{name}`. Diagrams match the dark admin theme. No internet or CDN
dependency (assets vendored in the bundle).

## Non-goals

- No change to the DB-diagram feature (`DocDiagramController` serves
  pre-generated `.svg` files — unrelated).
- No server-side diagram rendering.
- No support for mermaid in other views (only the markdown doc item page).

## Approach

Client-side rendering. Mermaid.js runs in the browser, so the whole feature is
**vendored assets + one template change**. No PHP/controller/parser changes.

Parsedown's output is already identifiable by the `language-mermaid` class, so
no server-side HTML rewrite is needed. Reading a node's `.textContent` in JS
naturally decodes Parsedown's HTML-escaping back to the raw mermaid source
(`-->`, `<br>`, etc.), which is exactly what mermaid expects.

## Changes

### 1. Vendored asset — `public/js/mermaid.min.js`

The mermaid library, dropped into `public/js/` following the existing
convention (`chart.js`, `jquery.js`, `moment.min.js`). Installed to the project
as `public/bundles/easyadminhelper/js/mermaid.min.js` via
`assets:install`, and referenced with
`asset('bundles/easyadminhelper/js/mermaid.min.js')`.

Pin a specific mermaid version so the vendored file is reproducible; record the
version in a short comment in the template near the script tag.

### 2. Template — `templates/admin/doc/item.html.twig`

Add an EasyAdmin `{% block body_javascript %}` (loads after page content) that:

1. Loads `mermaid.min.js` via `<script src="{{ asset(...) }}">`.
2. Runs an **inline** init script that:
   - Calls `mermaid.initialize({ startOnLoad: false, theme: 'dark', securityLevel: 'strict' })`.
   - Selects every `code.language-mermaid` in `.doc-item`.
   - For each, creates a `<pre class="mermaid">` whose text is the node's
     `.textContent`, and replaces the enclosing `<pre><code>` with it.
   - Calls `mermaid.run()` to render all collected nodes.
   - Guards on `typeof mermaid !== 'undefined'` so a blocked/failed script
     leaves the original code block visible as a fallback.

Optional small CSS (in the existing `head_stylesheets` block) so rendered
diagrams are not boxed by the `pre` code styling and center nicely.

### 3. No PHP changes

`FrontmatterParser`, controllers, and DI are untouched.

## Error handling

- **Diagram syntax error:** mermaid renders its own inline error panel for that
  block; other diagrams on the page still render.
- **Script blocked / mermaid undefined:** init is guarded; the page falls back
  to showing the raw fenced code (current behavior). No JS exception thrown.

## Testing

The feature is client-side JS, which the PHPUnit suite cannot exercise.
Verification is **manual in the browser**:

1. Open a doc containing a ` ```mermaid ` block (e.g. the business-logic doc from
   the screenshots).
2. Confirm the `stateDiagram-v2` block renders as an SVG diagram in the dark
   theme, and that `<br>` line breaks and `-->` transitions render correctly.
3. Confirm a non-mermaid code block still renders as a normal code block.

No automated test is added (the only PHP-testable seam — asserting the template
string references the asset — is low value and intentionally skipped).

## Rollout notes

- Projects consuming the bundle run `php bin/console assets:install` (already
  required for the existing vendored JS) to pick up `mermaid.min.js`.
