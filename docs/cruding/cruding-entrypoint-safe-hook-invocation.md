# Cruding entrypoint safe hook invocation

Cruding resolves granular URI-derived entrypoints, but entrypoint hooks are optional.
A component service may be empty, may extend the Cruding abstract entrypoint class,
may implement explicit method interfaces, or may be missing entirely.

The invoker must therefore be fail-soft:

- explicit registered service/key lookup remains first priority;
- URI-derived class lookup remains second priority;
- empty classes continue through Cruding default behavior;
- `isGrounded()` is optional;
- `get()`, `post()`, `put()`, `patch()`, and `delete()` are optional;
- private/protected hook-like methods must not be called;
- failed hook invocation degrades to diagnostics and default CRUD behavior.

The invoker uses public-callable checks instead of raw `method_exists()` for hook dispatch.
This matters because `method_exists()` can see private/protected methods that cannot be
called safely from the invoker.

Canonical result statuses:

- `no_entrypoint_override`
- `not_grounded`
- `entrypoint_grounding_failed`
- `entrypoint_hook_failed`
- `invalid_entrypoint_result_ignored`

This preserves the main rule: Cruding owns the engine; components own small,
self-documenting extension points.
