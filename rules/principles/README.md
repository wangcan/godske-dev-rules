# Cross-Cutting Principles

These rules apply across the entire codebase - both backend (PHP) and frontend (TypeScript/Vue).

## Available Rules

| Rule File | Loaded For | Covers |
|-----------|------------|--------|
| `use-software-engineer-skill.md` | `app/**/*.php`, `resources/js/**/*.{vue,ts,tsx}`, `tests/**/*.php`, `e2e/**/*.ts` | **MUST use `software-engineer` skill before writing code** |
| `simple-predictable-workflows.md` | `app/**/*.php`, `resources/js/**/*.{vue,ts,tsx}` | Avoiding cascading fallbacks, explicit failure modes |
| `no-hardcoded-database-entities.md` | `app/**/*.php`, `resources/js/**/*.{vue,ts,tsx}`, `e2e/**/*.ts` | Never hardcode database IDs/names, use generated enums |
| `nested-data-structures.md` | All files | Prefer nested objects over flat ID arrays in DTOs/APIs |

## Why These Are Separate

Some principles are language-agnostic and apply regardless of whether you're writing PHP or TypeScript. Rather than duplicating rules in both `backend/` and `frontend/`, cross-cutting concerns live here.

## Key Principles

1. **Use `software-engineer` Skill** - ALWAYS use the skill before writing any code. It orchestrates implementation with mandatory review cycles that catch convention violations.
2. **Simple, Predictable Workflows** - Code should follow one clear path. Configured options must be honored or fail explicitly - never cascade through alternatives silently.
3. **No Hardcoded Database Entities** - Never hardcode database entity IDs or names. Always use PHP enums with `#[Typescript]` annotation to generate TypeScript types, creating a single source of truth across backend, frontend, and E2E tests.
4. **Nested Data Structures** - Group related data in nested objects, don't scatter across parallel arrays. Enables per-item configuration and extensibility.
