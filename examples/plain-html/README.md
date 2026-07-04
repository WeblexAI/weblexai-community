# Plain HTML Example

Run a static page that loads the WeblexAI browser SDK from your installation.

```bash
python -m http.server 4173
```

Open `http://localhost:4173`, then fill in:

- WeblexAI URL, for example `http://localhost:8787`
- Project API key from the project setup page

Before testing, add this accepted origin to the project:

```text
http://localhost:4173
```

If the SDK loads but translations are rejected, check the accepted origin and API key first.
