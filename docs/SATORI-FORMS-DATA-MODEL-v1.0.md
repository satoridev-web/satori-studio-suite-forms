# SATORI Forms — Data Model

Version: 1.0  
Status: Authoritative (Phase F1)  
Applies To: SATORI Forms Core (Engine)

---

## 1. Purpose

This document defines the **authoritative data model** for SATORI Forms during **Phase F1 (Forms Core)**.

It exists to:
- Lock the structural contract of the Forms engine
- Prevent schema drift during implementation
- Provide a stable foundation for future rendering and Studio integration
- Act as the single source of truth for form and submission data

This document is **prescriptive**, not descriptive. Engine code must conform to it.

---

## 2. Design Principles

- WordPress remains the system of record
- Forms are configuration data, not UI constructs
- Submissions are immutable records
- No implicit or inferred fields
- All validation rules are explicit
- Extensibility is allowed only through additive fields

---

## 3. Core Entities Overview

The Forms engine defines three primary data entities:

1. Form (definition)
2. Field (within a form)
3. Submission (instance data)

Relationships:
- One Form has many Fields
- One Form has many Submissions
- One Submission belongs to one Form

---

## 4. Form Entity

### Storage

- WordPress Custom Post Type: `satori_form`
- Post status controls lifecycle (draft, publish, trash)

### Core Properties

| Property | Type | Notes |
|--------|------|------|
| ID | int | WordPress post ID |
| post_title | string | Form name |
| post_status | string | Draft / Publish |
| post_date | datetime | Managed by WordPress |

### Meta: `satori_form_schema`

Single JSON-serialised array defining the form structure.

```
{
  "version": 1,
  "fields": [ ... ],
  "settings": { ... }
}
```

---

## 5. Field Entity

Fields exist **only within a Form schema**. They are not standalone records.

### Field Object Structure

```
{
  "id": "string",          // unique within form
  "type": "string",        // field type
  "label": "string",
  "required": true|false,
  "validation": { ... },
  "meta": { ... }
}
```

### Supported Field Types (Phase F1)

- text
- email
- textarea

No other field types are valid in Phase F1.

### Validation Object

```
{
  "required": true|false,
  "min_length": int|null,
  "max_length": int|null
}
```

Validation is enforced server-side only.

---

## 6. Form Settings Object

```
{
  "notifications": {
    "enabled": true|false,
    "to": "email@domain.com",
    "subject": "string",
    "message": "string"
  }
}
```

No templating system beyond simple placeholders is implied.

---

## 7. Submission Entity

### Storage Strategy

- Implemented as a **dedicated database table** (Phase F1)
- Submissions are immutable once written

(Table name to be finalised during implementation, prefixed by `$wpdb->prefix`)

### Submission Record Structure

| Column | Type | Notes |
|-------|------|------|
| id | bigint | Primary key |
| form_id | bigint | References `satori_form` ID |
| data | longtext | JSON-encoded submitted values |
| submitted_at | datetime | UTC |
| ip_address | varchar | Optional, nullable |

No user ID is stored in Phase F1.

---

## 8. Submission Data Payload

```
{
  "field_id": "submitted value",
  "field_id_2": "submitted value"
}
```

Only fields defined in the form schema may appear.

---

## 9. Validation Rules

- Every submission is validated against the stored form schema
- Unknown fields are rejected
- Missing required fields cause failure
- Sanitisation uses WordPress core functions

---

## 10. Versioning & Forward Compatibility

- `version` key in form schema is mandatory
- Future versions may add fields but must not remove or rename existing keys
- Engine must gracefully reject unsupported schema versions

---

## 11. Explicit Non-Goals

This data model does not include:
- Conditional logic
- Multi-step flow state
- Styling or presentation data
- CRM mappings
- Analytics or tracking

---

## 12. Authority

This document is authoritative for:
- Phase F1 Codex plans
- Engine implementation
- Review and acceptance criteria

Any engine behaviour that conflicts with this model is considered a defect.

---

End of Document

