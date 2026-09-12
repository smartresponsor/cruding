# Security Policy

Cruding sits on an HTTP, routing, authorization-context, form, and persistence boundary. Security defects in route classification, ownership resolution, mutation authorization, request decoding, or generic fallback behavior should therefore be treated as component-level security issues.

## Reporting

Report suspected vulnerabilities through the repository owner's established private security/contact channel. Do not publish exploitable details in a public issue before the maintainers have had an opportunity to reproduce and remediate the problem.

Include, where possible:

- affected route or operation;
- expected and observed authorization behavior;
- minimal reproduction steps;
- relevant request attributes/configuration;
- whether the issue exposes, mutates, or deletes data across an ownership boundary;
- proposed mitigation if already known.

## High-priority classes of issue

- authorization or ownership bypass;
- route-token ambiguity that reaches an unintended operation;
- unsafe fallback from a specific provider/service to a generic mutation path;
- entity-class or form-type resolution to an unintended host class;
- mass-assignment or form-submission behavior that writes fields outside the intended contract;
- information disclosure through diagnostics, exceptions, route manifests, or API problem responses.

## Validation expectation

Security fixes must include a regression test or deterministic smoke guard whenever the behavior can be reproduced locally. Run `composer check:cruding` before considering the change complete.

This file defines the reporting and validation process; it does not replace the host application's own authentication, authorization, secret-management, or incident-response policies.
