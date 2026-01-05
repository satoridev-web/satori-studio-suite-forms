# SATORI Forms

SATORI Forms is a standalone-first WordPress forms engine designed to integrate
seamlessly with the broader SATORI platform, including SATORI Studio.

This repository is intentionally decoupled from Builder-specific concerns.
The Forms engine must be useful and stable even without SATORI Studio installed.

---

## Project Status

- Phase: F1 — Forms Core (Engine)
- Status: Initialisation
- Integration with SATORI Studio: Deferred (later phase)

---

## Guiding Principles

- Standalone-first (engine before UI modules)
- WordPress lifecycle remains authoritative
- Stability over novelty
- Progressive convergence with the SATORI Platform
- ACF Free may be used for admin convenience only (not a runtime dependency)

---

## Authoritative Standards

This project follows the same standards and workflows as SATORI Studio:

- SATORI Product Delivery Principles
- SATORI Studio Standards
- WORKFLOW-SATORI-AUTOMATED-DEVELOPMENT
- SATORI SOPs — Automated Development Directives
- Codex Development Guide
- Standard Pull Request Template

Refer to those documents in the SATORI Studio repository.

---

## Getting Started (Local Development)

- Environment: LocalWP
- PHP / WP versions: match SATORI Studio
- Editor: VS Code (same extensions as Studio)
- Git workflow: PR-only, squash merges

---

## Next Step

Read `docs/SATORI-FORMS-ROADMAP-v1.0.md` and begin Phase F1 planning.