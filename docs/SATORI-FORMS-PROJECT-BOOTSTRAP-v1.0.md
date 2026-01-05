# SATORI Forms — Project Bootstrap Pack

Version: 1.0  
Status: Initiation  
Date: 2025-12-30

## Purpose

This document defines how the **SATORI Forms** project is initiated, structured, and governed.

SATORI Forms is a **standalone-first domain system** that will later integrate with SATORI Studio.

---

## Guiding Principles

- Standalone-first (useful without Studio)
- WordPress lifecycle remains authoritative
- Stability over novelty
- Progressive convergence with SATORI Platform
- ACF Free may be used as an admin convenience, not a runtime dependency

---

## Reused Authoritative Standards

The following documents apply directly:

- SATORI Product Delivery Principles
- SATORI Studio Standards v1.0
- WORKFLOW-SATORI-AUTOMATED-DEVELOPMENT v1.1
- SATORI SOPs — Automated Development Directives
- Codex Development Guide
- Standard Pull Request Template

No duplication required.

---

## Repository Setup

Repository Name:
- satori-studio-suite-forms

Local Environment:
- LocalWP
- Same PHP + WP versions as Studio
- VS Code with identical extensions

---

## Required Documents (Phase F1)

Create the following in /docs:

1. SATORI-FORMS-ROADMAP-v1.0.md
2. SATORI-FORMS-RESPONSIBILITY.md
3. SATORI-FORMS-DATA-MODEL.md

---

## Phase F1 Scope (Forms Core)

### In Scope
- Custom Post Type: satori_form
- Form schema definition
- Submission storage
- Basic validation
- Email notifications
- WP Admin management UI

### Out of Scope
- Studio modules
- Styling systems
- Multi-step forms
- Conditional logic
- CRM integration
- Pro features

---

## Development Order

1. Write Roadmap
2. Define Responsibility
3. Define Data Model
4. Create Codex Phase F1 plan
5. Implement engine only
6. No Studio integration until later phase

---

## Definition of Done (Phase F1)

- Forms can be created and stored
- Submissions are captured reliably
- Admin UI is usable
- No dependency on Studio

---

## Next Action

Draft:
- SATORI-FORMS-ROADMAP-v1.0.md

---

End of Bootstrap Pack
