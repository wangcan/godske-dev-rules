---
paths: e2e/components/**/*.ts
---

# E2E Component Conventions

> **Global Vue components get corresponding E2E component classes. 1:1 mapping with `resources/js/Components/`.**

## Core Principle

E2E components mirror `resources/js/Components/` exactly, just as page objects mirror `resources/js/Pages/`.

```
Vue Component                                    E2E Component
─────────────────────────────────────────────────────────────────────────
resources/js/Components/Filters/QueryBuilder.vue  →  e2e/components/Filters/QueryBuilder.component.ts
resources/js/Components/Modals/ConfirmModal.vue   →  e2e/components/Modals/ConfirmModal.component.ts
resources/js/Components/Tables/DataTable.vue      →  e2e/components/Tables/DataTable.component.ts
```

## Directory Structure

```
e2e/components/
├── Filters/
│   ├── QueryBuilder.component.ts   # Top-level - manages groups
│   ├── QueryGroup.component.ts     # Group of sentences with AND/OR
│   └── QuerySentence.component.ts  # Individual filter row
├── Modals/
│   └── ConfirmModal.component.ts
└── Tables/
    └── DataTable.component.ts
```

**No barrel exports** - import directly from component files for better scalability.

## Component Hierarchies

When Vue components have parent-child relationships, E2E components should mirror that structure:

```
Vue Hierarchy                          E2E Hierarchy
─────────────────────────────────────────────────────────────────
QueryBuilder.vue                       QueryBuilderComponent
  └── QueryGroup.vue                     └── QueryGroupComponent
        └── QuerySentence.vue                  └── QuerySentenceComponent
```

Each E2E component:
- Takes a `Locator` container that scopes it to a specific DOM section
- Provides methods to access child components
- Delegates to child components for granular operations

```typescript
// Parent component provides access to children
const group = queryBuilder.getGroup(0)          // Returns QueryGroupComponent
const sentence = group.getSentence(0)           // Returns QuerySentenceComponent

// Interact at any level of the hierarchy
await sentence.selectField('Name')
await sentence.selectCondition('Contains')
await sentence.enterCriteria('John')

// Or use convenience methods on the parent
await queryBuilder.addAndConfigureFilter('Name', 'Contains', 'John')
```

## When to Create E2E Components

Create an E2E component class when a Vue component:
1. Is used across **multiple pages** in different domains
2. Has **interactive behavior** that tests need to control
3. Would cause **duplication** if inlined in each page object

**Examples:**
- `QueryBuilder` - Used on many list pages for filtering
- `ConfirmModal` - Appears after delete/submit actions across the app
- `DataTable` - Complex table with sorting, pagination, row selection

## Component Completeness Checklist

Before considering an E2E component complete, verify it supports:

1. **All user interactions** the Vue component supports
   - Every button, link, or clickable element
   - Every input field (text, select, date picker, etc.)
   - Every form control (checkboxes, radio buttons, toggles)

2. **All possible workflows** a user might perform
   - Creating/adding new items
   - Editing existing items
   - Deleting/removing items
   - Navigating between states

3. **Meaningful assertions** about component state
   - Content visibility/values
   - Error states
   - Count/quantity assertions

### ❌ WRONG: Minimal Component

```typescript
// BAD - Only handles one action, can't actually configure anything
export class FilterComponent {
  async addFilter() {
    await this.addButton.click() // Opens filter but can't configure it!
  }
}
```

### ✅ CORRECT: Complete Component

```typescript
// GOOD - Handles full filter workflow
export class FilterComponent {
  // Add and configure a complete filter
  async addFilter(field: string, operator: string, value: string) {
    await this.addButton.click()
    await this.selectField(field)
    await this.selectOperator(operator)
    await this.enterValue(value)
  }

  // Individual control methods for flexibility
  async selectField(field: string) { /* ... */ }
  async selectOperator(operator: string) { /* ... */ }
  async enterValue(value: string) { /* ... */ }

  // Edit existing filters
  getFilterRow(index: number): Locator { /* ... */ }
  async removeFilter(index: number) { /* ... */ }

  // Assertions
  async expectFilterCount(count: number) { /* ... */ }
}
```

**Rule of thumb:** If you can't write a test that fully exercises the component's behavior, it's incomplete.

## Component Template

```typescript
import { type Locator, type Page, expect } from '@playwright/test'

/**
 * E2E component for QueryBuilder filter.
 * Mirrors: resources/js/Components/Filters/QueryBuilder.vue
 */
export class QueryBuilderComponent {
  readonly page: Page
  readonly container: Locator

  // Filter controls
  readonly addFilterButton: Locator
  readonly clearFiltersButton: Locator
  readonly applyButton: Locator

  constructor(page: Page, container?: Locator) {
    this.page = page
    // Allow scoping to a specific container if multiple on page
    this.container = container ?? page.locator('[data-testid="query-builder"]')

    this.addFilterButton = this.container.getByRole('button', { name: /add filter/i })
    this.clearFiltersButton = this.container.getByRole('button', { name: /clear/i })
    this.applyButton = this.container.getByRole('button', { name: /apply/i })
  }

  async addFilter(field: string, operator: string, value: string) {
    await this.addFilterButton.click()
    // Implementation depends on QueryBuilder's UI
    await this.container.getByLabel(/field/i).selectOption(field)
    await this.container.getByLabel(/operator/i).selectOption(operator)
    await this.container.getByLabel(/value/i).fill(value)
  }

  async apply() {
    await this.applyButton.click()
  }

  async clear() {
    await this.clearFiltersButton.click()
  }

  async expectFilterCount(count: number) {
    const filters = this.container.locator('[data-testid="filter-row"]')
    await expect(filters).toHaveCount(count)
  }
}
```

## Using Components in Page Objects

Page objects compose E2E components as properties:

```typescript
import { QueryBuilderComponent } from '@e2e/components/Filters/QueryBuilder.component'

export class OrdersIndexPage {
  readonly page: Page
  readonly filters: QueryBuilderComponent

  constructor(page: Page) {
    this.page = page
    this.filters = new QueryBuilderComponent(page)
  }

  async filterByStatus(status: string) {
    await this.filters.addFilter('status', 'equals', status)
    await this.filters.apply()
  }
}
```

## Modal Component Pattern

Modals often appear after actions. Use a method that returns the component:

```typescript
// e2e/components/Modals/ConfirmModal.component.ts
export class ConfirmModalComponent {
  readonly page: Page
  readonly dialog: Locator
  readonly confirmButton: Locator
  readonly cancelButton: Locator
  readonly message: Locator

  constructor(page: Page) {
    this.page = page
    this.dialog = page.getByRole('dialog')
    this.confirmButton = this.dialog.getByRole('button', { name: /confirm|yes|delete/i })
    this.cancelButton = this.dialog.getByRole('button', { name: /cancel|no/i })
    this.message = this.dialog.locator('[data-testid="confirm-message"]')
  }

  async expectOpen() {
    await expect(this.dialog).toBeVisible()
  }

  async expectClosed() {
    await expect(this.dialog).not.toBeVisible()
  }

  async confirm() {
    await this.confirmButton.click()
  }

  async cancel() {
    await this.cancelButton.click()
  }

  async expectMessage(text: string | RegExp) {
    await expect(this.message).toContainText(text)
  }
}

// In page object:
export class OrdersIndexPage {
  readonly page: Page

  constructor(page: Page) {
    this.page = page
  }

  async clickDeleteFor(orderId: string) {
    await this.getRow(orderId).getByRole('button', { name: /delete/i }).click()
  }

  /** Returns modal component for interaction after delete click */
  confirmDeleteModal(): ConfirmModalComponent {
    return new ConfirmModalComponent(this.page)
  }
}

// In test:
await ordersPage.clickDeleteFor('ORD-123')
const modal = ordersPage.confirmDeleteModal()
await modal.expectOpen()
await modal.expectMessage(/are you sure/i)
await modal.confirm()
await modal.expectClosed()
```

## Import Pattern

Import directly from component files (no barrel exports for better scalability):

```typescript
// Direct imports
import { QueryBuilderComponent } from '@e2e/components/Filters/QueryBuilder.component'
import { ConfirmModalComponent } from '@e2e/components/Modals/ConfirmModal.component'
import { DataTableComponent } from '@e2e/components/Tables/DataTable.component'
```

## Naming Conventions

| Type | File Name | Class Name |
|------|-----------|------------|
| Component | `{Name}.component.ts` | `{Name}Component` |

Same as page-specific components - the `.component.ts` suffix distinguishes from page objects.

## Scoped Components

When a component may appear multiple times on a page, accept an optional container:

```typescript
export class TabsComponent {
  constructor(page: Page, container?: Locator) {
    this.container = container ?? page.locator('[role="tablist"]')
  }
}

// Usage - scoped to specific section
const headerTabs = new TabsComponent(page, page.locator('header'))
const mainTabs = new TabsComponent(page, page.locator('main'))
```
