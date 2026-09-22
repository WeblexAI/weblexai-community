# Post-Implementation Review Workflow

After any code implementation or code modification:

1. Run the `laravel-simplifier` agent first.
2. Limit simplification to code changed during the current task or session unless explicitly told otherwise.
3. Run the `code-reviewer` agent against the final changed code.
4. If the reviewer finds important issues, fix them and run the review again.

Do not run this workflow for explanation-only, documentation-only, planning-only, or read-only tasks, PR reviews, or bug investigations where no code was changed.
