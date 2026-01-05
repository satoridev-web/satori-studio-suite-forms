# SATORI Forms — Responsibility Definition

Version: 1.0  
Status: Authoritative  
Date: 2026-01-06

---

## 1. Purpose

This document defines the **explicit responsibility boundaries** of the SATORI Forms project.

Its purpose is to ensure:
- Clear ownership of concerns
- Strict scope control
- Consistent decision-making
- Safe future integration with other SATORI products

This document is authoritative for **all planning, Codex instructions, and implementation work** related to SATORI Forms.

---

## 2. What SATORI Forms *IS*

SATORI Forms is a **standalone-first WordPress forms engine**.

It is responsible for:

- Defining and managing forms as structured data
- Defining form fields and field schemas
- Handling validation rules and submission constraints
- Capturing and storing form submissions reliably
- Providing basic submission notifications (e.g. email)
- Providing a WordPress admin interface for managing forms and submissions
- Operating correctly without SATORI Studio installed

SATORI Forms prioritises **engine correctness, data integrity, and stability** over UI sophistication.

---

## 3. What SATORI Forms *IS NOT*

SATORI Forms is explicitly **not responsible** for:

- Page building or layout composition
- Visual design systems or styling controls
- Theme-level presentation concerns
- Advanced frontend interactivity
- CRM functionality or contact management
- Marketing automation or workflows
- Analytics, tracking, or attribution
- Payment processing
- User authentication or membership logic
- Acting as a general-purpose application framework

SATORI Forms does **not** attempt to replace:
- Page builders
- CRMs
- Marketing platforms
- Design tools

---

## 4. Phase F1 Responsibility Boundary (Forms Core)

During **Phase F1 (Forms Core)**, responsibility is further constrained.

### In Phase F1, SATORI Forms *does*:

- Implement the core forms engine
- Define internal data structures
- Persist form and submission data
- Provide minimal admin UI for management
- Operate independently of any builder or UI framework

### In Phase F1, SATORI Forms *does not*:

- Provide frontend styling systems
- Provide drag-and-drop builders
- Implement conditional logic
- Implement multi-step forms
- Integrate with external services
- Integrate with SATORI Studio

Any feature not required for a **minimal, reliable forms engine** is out of scope for Phase F1.

---

## 5. Integration Policy (Future Phases)

SATORI Forms may integrate with other SATORI products in later phases.

All integrations must follow these rules:

- SATORI Forms remains the **system of record** for form data
- Integrations occur via **explicit adapters or modules**
- SATORI Forms does not reach into other products’ internals
- Other products consume Forms via stable, documented interfaces
- The Forms engine is never rewritten to satisfy a specific integration

SATORI Studio integration, when introduced, must:
- Treat SATORI Forms as an external engine
- Respect Forms’ responsibility boundaries
- Avoid introducing coupling into the Forms core

---

## 6. Non-Goals (Permanent)

The following are **permanent non-goals** for SATORI Forms:

- Becoming a visual design tool
- Becoming a CRM replacement
- Becoming a marketing automation platform
- Becoming Studio-dependent
- Optimising for novelty over reliability

---

## 7. Authority & Change Control

Changes to this document:

- Require explicit intent
- Must be reviewed with respect to platform-wide impact
- Must not be driven by short-term feature pressure

If a proposed change conflicts with this document, the change is **out of scope** unless this document is first amended.

---

End of Responsibility Definition
