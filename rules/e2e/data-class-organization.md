---
paths: app/Data/Controllers/E2E/**/*.php
---

# E2E Data Class Organization

E2E Data classes follow the **same patterns** as App/API controllers. See `controller-conventions.md` for:
- `#[TypeScript]` annotation usage
- Static factory methods
- Data class structure

## E2E-Specific Directory Structure

```
app/Data/Controllers/E2E/
├── TeamController/
│   └── CreateTeamResponseData.php
├── UserController/
│   ├── CreateUserRequestData.php
│   └── CreateUserResponseData.php
└── SettingsController/
    └── UpdateSettingsRequestData.php
```

**Pattern:** `app/Data/Controllers/E2E/[ControllerName]/[DataClass].php`

This mirrors the pattern used by App/API controllers, keeping the codebase consistent.
