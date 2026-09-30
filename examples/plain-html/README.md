# Plain HTML Example

Run a static page that loads the WeblexAI browser SDK from your installation.

```bash
python -m http.server 4173
```

Python is only needed to serve this local example page.

Open `http://localhost:4173`, then fill in:

- The URL where your WeblexAI installation is reachable, for example `http://localhost:8787`
- The project API key shown in **Project Setup** after you rotate it from the project details page

Before testing, add this accepted origin to the project:

```text
http://localhost:4173
```

If the SDK loads but translations are rejected, check the accepted origin and API key first.
