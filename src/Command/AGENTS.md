Console commands must follow Symfony best practices.
- Use top-level command names (e.g. `user:promote`) without an `app:` prefix.
- Keep business logic in dedicated services; commands should orchestrate inputs and outputs only.
