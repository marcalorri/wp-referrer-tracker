---
trigger: always_on
---
# General project rules

For this WordPress plugin project:

- Keep changes minimal and focused on the requested task.
- Do not add or remove comments/documentation unless the task requires it.
- Preserve WordPress coding compatibility with PHP 7.4+.
- Do not hardcode API keys, secrets, or customer-specific values.
- Prefer WordPress APIs for escaping, sanitization, options, hooks, and admin output.
- Treat Intelephense undefined WordPress function warnings as expected unless PHP syntax validation fails.
- Validate PHP syntax with `php -l` for modified PHP files.
- Before release work, confirm the current Git branch, changed files, latest tag, and intended next semantic version.
