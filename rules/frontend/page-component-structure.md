---
paths:
  - "resources/js/Pages/**/*.vue"
---

# Page Component Structure

> **Every route has its own Vue page component. Shared UI goes in `_components/`.**

## Core Convention

```
resources/js/Pages/App/Users/
├── Index.vue              ← Route: /app/users (list page)
├── Create.vue             ← Route: /app/users/create
├── Edit.vue               ← Route: /app/users/{id}/edit
├── Show.vue               ← Route: /app/users/{id}
└── _components/
    └── Form.vue           ← Shared form (NOT a page)
```

## Why This Structure?

1. **1:1 mapping** - Every route has exactly one page file
2. **Predictable** - Know exactly where to find/create page files
3. **E2E-friendly** - Page objects can mirror this structure exactly
4. **Clear boundaries** - `_components/` signals "not a page"

## Page Types

| File | Route Pattern | Purpose |
|------|---------------|---------|
| `Index.vue` | `/resource` | List/table view |
| `Create.vue` | `/resource/create` | New resource form |
| `Edit.vue` | `/resource/{id}/edit` | Edit resource form |
| `Show.vue` | `/resource/{id}` | Read-only detail view |

## The `_components/` Directory

Use `_components/` for shared UI within a page group:

```typescript
// _components/ contains reusable pieces
resources/js/Pages/App/Users/_components/
├── Form.vue              // Shared between Create/Edit
├── AddressSection.vue    // Form section
└── PermissionsTable.vue  // Reusable table
```

**Why underscore prefix?**
- Visually signals "not a route page"
- Sorts to top of directory listing
- Matches convention in other frameworks (Next.js, Nuxt)

## Create/Edit Pattern

Create.vue and Edit.vue should be **thin wrappers** that import shared form components:

```vue
<!-- Create.vue -->
<script setup lang="ts">
import Form from './_components/Form.vue'
import { useForm } from '@inertiajs/vue3'

const form = useForm({
  name: '',
  email: '',
})

const submit = () => {
  form.post(route('users.store'))
}
</script>

<template>
  <PageHeader title="Create User" />
  <Form :form="form" @submit="submit" />
</template>
```

```vue
<!-- Edit.vue -->
<script setup lang="ts">
import Form from './_components/Form.vue'
import { useForm } from '@inertiajs/vue3'

const props = defineProps<{
  user: App.Data.UserData
}>()

const form = useForm({
  name: props.user.name,
  email: props.user.email,
})

const submit = () => {
  form.put(route('users.update', props.user.id))
}
</script>

<template>
  <PageHeader :title="`Edit ${user.name}`" />
  <Form :form="form" @submit="submit" />
</template>
```

```vue
<!-- _components/Form.vue -->
<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3'

defineProps<{
  form: InertiaForm<{ name: string; email: string }>
}>()

const emit = defineEmits<{
  submit: []
}>()
</script>

<template>
  <form @submit.prevent="emit('submit')">
    <FormInput v-model="form.name" label="Name" :error="form.errors.name" />
    <FormInput v-model="form.email" label="Email" :error="form.errors.email" />
    <Button type="submit" :loading="form.processing">Save</Button>
  </form>
</template>
```

## Anti-Patterns

### Single Form.vue for Multiple Routes

```
❌ BAD - Ambiguous which routes use this
resources/js/Pages/App/Users/
├── Index.vue
└── Form.vue    ← Used for both create AND edit routes
```

```
✅ GOOD - Clear 1:1 mapping
resources/js/Pages/App/Users/
├── Index.vue
├── Create.vue
├── Edit.vue
└── _components/
    └── Form.vue
```

### Deeply Nested Components

```
❌ BAD - Too deep
_components/
└── forms/
    └── sections/
        └── UserForm.vue

✅ GOOD - Flat structure
_components/
├── Form.vue
├── AddressSection.vue
└── PermissionsSection.vue
```

## When to Create Pages vs Components

| Scenario | Create |
|----------|--------|
| New route needed | Page file (e.g., `Create.vue`) |
| Shared between pages | `_components/` file |
| Used only within one page | Keep inline or local component |
| Reused across domains | `resources/js/Components/` |
