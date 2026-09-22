# Laravel coding rules

- Follow the existing directory structure and match sibling files when adding or changing code.
- Keep controllers thin: authorize, validate with Form Requests, delegate application logic, and return responses consistent with the surrounding code.
- Use Eloquent directly for straightforward persistence and relationships. Avoid adding repository or query layers without a clear need.
- Keep reusable application logic in the existing service and support classes.
- Use Laravel pipelines for ordered, multi-step workflows. Keep each pipeline stage focused and match the signatures used by existing pipelines.
- Use DTOs for structured data crossing application boundaries. Place them under `app/DTOs` and follow the existing namespace and naming conventions.
- Put finite domain values in backed enums under `app/Enums`.
- Put shared, non-domain helpers and adapters under `app/Support`.
- Use `php artisan make:*` commands for new Laravel classes, migrations, models, and tests when an applicable generator exists.
- Check installed package versions before using version-sensitive Laravel or package APIs.
- The frontend uses Vue 3, TypeScript, the Composition API, Inertia, Tailwind CSS, and Reka UI. Reuse existing components when possible.
- Put navigable frontend pages in `resources/js/pages` and reusable components in `resources/js/components`.
- Prefer Inertia props, forms, router visits, and existing composables. Do not introduce Pinia or a separate API layer without a clear boundary.
- Keep authorization decisions on the server and implement accessible loading, empty, error, disabled, focus, and keyboard states.
- Use Pest for PHP tests and add meaningful coverage for behavior and authorization.
