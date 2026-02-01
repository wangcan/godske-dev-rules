---
paths: resources/js/**/*.{vue,ts,tsx}
---

# Frontend Development Rules

These rules are **automatically loaded** when working on Vue/TypeScript files.

## Available Rules

| Rule File | Loaded For | Covers |
|-----------|------------|--------|
| `vue-conventions.md` | `resources/js/**/*.{vue,ts,tsx}` | defineModel(), Composition API, TypeScript patterns |
| `component-composition.md` | `resources/js/Components/**/*.vue` | Component size limits, single responsibility, when to split |
| `component-size-and-organization.md` | `resources/js/**/*.vue` | Hard limits enforcement, red flags, refactoring patterns for bloated components |
| `component-template-casing.md` | `resources/js/**/*.vue` | PascalCase for imported components in templates (prevents ESLint false positives) |

**See also:** `../principles/` for cross-cutting rules that apply to both backend and frontend.

## Quick Validation Checklist

Before submitting your code, scan for these red flags:

- [ ] Using `modelValue` prop + manual `emit('update:modelValue')`? → Should use `defineModel()`
- [ ] Component template over 150 lines? → **STOP and refactor NOW** (see `component-size-and-organization.md`)
- [ ] Component script over 200 lines? → **STOP and refactor NOW** (see `component-size-and-organization.md`)
- [ ] Multiple modals/dialogs inline? → Extract each to separate component
- [ ] Component handling multiple responsibilities (data fetching + forms + display)? → Should split concerns
- [ ] Missing TypeScript types on props? → Should use `PropType<T>` or interfaces
- [ ] Using Options API (`export default { data(), methods: {} }`)? → Should use Composition API (`<script setup>`)
- [ ] Deeply nested template structure (5+ levels)? → Should extract child components
- [ ] Any cascading fallback chains when configuration is set? → Should fail explicitly (see `../principles/`)
- [ ] Using kebab-case for imported components in template (e.g., `<confirm-modal>`)? → Should use PascalCase (`<ConfirmModal>`) to avoid ESLint false positives (see `component-template-casing.md`)
