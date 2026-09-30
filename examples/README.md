# WeblexAI Examples

These examples show the browser SDK in the smallest useful forms. Start with the plain HTML example because it needs no build step.

## Plain HTML

```bash
cd examples/plain-html
python -m http.server 4173
```

Python is only needed to serve this local example page.

Open `http://localhost:4173`.

In WeblexAI:

1. Add `http://localhost:4173` as an accepted origin for the project.
2. Open **Project Setup**. If no project API key appears, rotate it from the project details page.
3. Enter the URL where your WeblexAI installation is reachable and the project API key in the example page.
4. Click **Load WeblexAI**.

## Framework Examples

The React and Vue folders show where to place the SDK snippet in a real app layout. They are intentionally small so you can copy the pattern into an existing project.

Use your own WeblexAI URL and project API key. The website origin must match one accepted origin on the project.
