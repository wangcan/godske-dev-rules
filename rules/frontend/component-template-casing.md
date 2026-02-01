---
paths: resources/js/**/*.vue
---

# Component Template Casing Convention

> **Always use PascalCase for imported components in templates.**

## Why This Matters

When you import a component and use it with kebab-case in the template, ESLint's `@typescript-eslint/no-unused-vars` rule cannot detect that the import is being used:

```vue
<script setup>
// ESLint thinks ConfirmModal is unused!
import ConfirmModal from '@/Components/ConfirmModal.vue';
</script>

<template>
  <!-- kebab-case: ESLint can't see this usage -->
  <confirm-modal ref="modal" />
</template>
```

This leads to:
1. False "unused import" warnings/errors
2. Risk of accidentally removing "unused" imports
3. Confusion about what's actually being used

## The Rule

### ✅ DO: Use PascalCase for imported components

```vue
<script setup>
import ConfirmModal from '@/Components/ConfirmModal.vue';
import UserImage from '@/Components/App/UserImage.vue';
import NoResults from '@/Components/App/NoResults.vue';
</script>

<template>
  <ConfirmModal ref="modal" title="Confirm" />
  <UserImage :user="user" />
  <NoResults text="No items found" />
</template>
```

### ❌ DON'T: Use kebab-case for imported components

```vue
<script setup>
import ConfirmModal from '@/Components/ConfirmModal.vue';
</script>

<template>
  <!-- BAD: ESLint can't detect this usage -->
  <confirm-modal ref="modal" title="Confirm" />
</template>
```

## Exceptions: Global Components

Global components registered in `app.js` don't need imports, so kebab-case is acceptable:

```vue
<template>
  <!-- These are globally registered, no import needed -->
  <app-layout title="Dashboard">
    <app-content-box>
      <a-table>
        <a-th>Name</a-th>
        <a-td>Value</a-td>
      </a-table>
    </app-content-box>
  </app-layout>
</template>
```

**Global components (kebab-case OK):**
- `app-layout`, `adm-layout`
- `app-content-box`, `adm-content-box`
- `a-table`, `a-th`, `a-td`
- `check-box`, `text-area`, `datepicker`
- `content-header`

## ESLint Enforcement

This convention is enforced by ESLint rule `vue/component-name-in-template-casing`:
- Currently set to **warn** (gradual migration)
- Global components are in the ignore list
- New code should always use PascalCase

## Benefits of PascalCase

1. **ESLint detection** - Unused imports are properly detected
2. **Clear distinction** - Easy to see what's a component vs HTML element
3. **IDE support** - Better autocomplete and go-to-definition
4. **Consistency** - Same name in import and template
