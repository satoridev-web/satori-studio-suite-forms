# SATORI Forms — Roadmap v1.0

Version: 1.0  
Status: Draft  
Date: 2025-12-30

---

## Overview

This roadmap defines the phased development of SATORI Forms as a
standalone domain system that later integrates with SATORI Studio.

The emphasis is on engine-first correctness, data integrity,
and long-term extensibility.

---

## Phase F1 — Forms Core (Engine)

### Goal

Deliver a functional, stable forms engine that works independently
of SATORI Studio.

### In Scope

- Custom Post Type: satori_form
- Form schema definition
- Field definitions and validation rules
- Submission storage
- Basic email notifications
- WP Admin management UI
- Optional use of ACF Free for admin field management

### Out of Scope

- Studio modules
- Styling systems
- Frontend theming polish
- Multi-step forms
- Conditional logic
- CRM integration
- Pro features

### Definition of Done

- Forms can be created and stored
- Submissions are reliably captured
- Admin UI is usable
- No dependency on SATORI Studio

---

## Phase F2 — Rendering & Delivery

- Shortcode-based rendering
- Accessible HTML output
- Minimal JS
- Basic spam mitigation

---

## Phase F3 — Studio Integration

- SATORI Studio module(s) for Forms
- Adapter layer only (no engine rewrite)
- Respect Studio guardrails

---

## Phase F4 — Pro & Advanced Features (Future)

- Conditional logic
- Multi-step forms
- Integrations (CRM, Webhooks, etc.)
- Styling & design controls

---

End of Roadmap
