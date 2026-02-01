---
paths: e2e/pages/**/*.ts
---

# Page Object Model Conventions

> **Every Vue page gets a corresponding page object. 1:1 mapping, no exceptions.**
>
> **Tests interact ONLY through page objects. No raw locators in test files.**

## Core Principle: 1:1 Mapping

Page objects MUST mirror `resources/js/Pages/` exactly — **including all nested subdirectories**. If Vue pages are at `App/A/B/C/Index.vue`, page objects go at `App/A/B/C/Index.page.ts`. No flattening, no restructuring, regardless of how routes are organized.

```
Vue Page                                    E2E Page Object
─────────────────────────────────────────────────────────────────────
resources/js/Pages/Auth/Login.vue       →   e2e/pages/Auth/Login.page.ts
resources/js/Pages/App/Users/Index.vue  →   e2e/pages/App/Users/Index.page.ts
resources/js/Pages/App/Users/Create.vue →   e2e/pages/App/Users/Create.page.ts
resources/js/Pages/App/Users/Edit.vue   →   e2e/pages/App/Users/Edit.page.ts
```

**Why 1:1 mapping?**
- Predictable - know exactly where to find/create page objects
- Maintainable - Vue page changes → update corresponding page object
- AI-friendly - simple rule that's easy to follow

## Core Principle: No Raw Locators in Tests

**Tests should NEVER use raw Playwright locators.** Everything must go through the page object.

```typescript
// ❌ BAD - Raw locators in test
test('filters users', async ({ authenticatedPage }) => {
  const { page } = authenticatedPage
  await page.goto('/app/users')
  await page.getByRole('button', { name: 'Add filter' }).click()  // Raw locator!
  await page.locator('table tbody tr').count()  // Raw locator!
})

// ✅ GOOD - All interactions through page object
test('filters users', async ({ authenticatedPage }) => {
  const { page } = authenticatedPage
  const indexPage = new UsersIndexPage(page)
  await indexPage.goto()
  await indexPage.filters.clickAddFilter()
  await indexPage.table.expectRowCount(5)
})
```

**Why?**
- Single source of truth for locators
- When UI changes, update one place (page object), not every test
- Tests read like user stories, not implementation details
- Forces you to think: "What does a user need to do on this page?"

**If you need a locator in a test, the page object is incomplete.** Add the method/property to the page object first.

## Directory Structure

```
e2e/
├── components/                   # Global components (mirrors resources/js/Components/)
│   └── [see e2e-components.md]
├── pages/                        # Page objects (mirrors resources/js/Pages/)
│   ├── _base/                    # Base classes
│   ├── _shared/                  # Layout components only
│   ├── App/
│   │   └── Users/
│   │       ├── _components/      # Page-specific shared components
│   │       │   └── Form.component.ts
│   │       ├── Index.page.ts
│   │       ├── Create.page.ts
│   │       └── Edit.page.ts
│   └── index.ts                  # Barrel exports
└── fixtures/                     # Test fixtures
```

**Component locations:**
| Vue Location | E2E Location | Rule |
|--------------|--------------|------|
| `resources/js/Components/*` | `e2e/components/*` | See `e2e-components.md` |
| `resources/js/Pages/X/_components/*` | `e2e/pages/X/_components/*` | This file |
| `resources/js/Layouts/*` | `e2e/pages/_shared/*` | This file |

## Naming Conventions

| Type | File Name | Class Name |
|------|-----------|------------|
| Page object | `{Name}.page.ts` | `{Name}Page` |
| Shared component | `{Name}.component.ts` | `{Name}Component` |

```typescript
// e2e/pages/App/Users/Index.page.ts
export class UsersIndexPage { ... }

// e2e/pages/App/Users/_components/Form.component.ts
export class UsersFormComponent { ... }
```

## Page Object Template

```typescript
import { type Locator, type Page, expect } from '@playwright/test'

/**
 * Page Object for Users Index.
 * Mirrors: resources/js/Pages/App/Users/Index.vue
 */
export class UsersIndexPage {
  readonly page: Page

  // Locators
  readonly heading: Locator
  readonly createButton: Locator
  readonly table: Locator

  constructor(page: Page) {
    this.page = page
    this.heading = page.getByRole('heading', { name: /users/i })
    this.createButton = page.getByRole('link', { name: /create/i })
    this.table = page.getByRole('table')
  }

  // Navigation
  async goto() {
    await this.page.goto('/app/users')
    await this.page.waitForLoadState('networkidle')
  }

  // Actions
  async clickCreate() {
    await this.createButton.click()
  }

  // Assertions
  async expectLoaded() {
    await expect(this.heading).toBeVisible()
  }
}
```

## Create/Edit Pages with Shared Form

When Create.vue and Edit.vue share a form component in Vue, mirror this in page objects:

```typescript
// e2e/pages/App/Users/_components/Form.component.ts
export class UsersFormComponent {
  readonly page: Page
  readonly nameInput: Locator
  readonly emailInput: Locator
  readonly submitButton: Locator

  constructor(page: Page) {
    this.page = page
    this.nameInput = page.getByLabel(/name/i)
    this.emailInput = page.getByLabel(/email/i)
    this.submitButton = page.getByRole('button', { name: /save/i })
  }

  async fill(data: { name?: string; email?: string }) {
    if (data.name) await this.nameInput.fill(data.name)
    if (data.email) await this.emailInput.fill(data.email)
  }

  async submit() {
    await this.submitButton.click()
  }
}

// e2e/pages/App/Users/Create.page.ts
import { UsersFormComponent } from './_components/Form.component'

export class UsersCreatePage {
  readonly page: Page
  readonly form: UsersFormComponent

  constructor(page: Page) {
    this.page = page
    this.form = new UsersFormComponent(page)
  }

  async goto() {
    await this.page.goto('/app/users/create')
  }
}

// e2e/pages/App/Users/Edit.page.ts
import { UsersFormComponent } from './_components/Form.component'

export class UsersEditPage {
  readonly page: Page
  readonly form: UsersFormComponent

  constructor(page: Page, private userId: number) {
    this.page = page
    this.form = new UsersFormComponent(page)
  }

  async goto() {
    await this.page.goto(`/app/users/${this.userId}/edit`)
  }
}
```

## Locator Priority

**Use in order of preference:**

1. **Role-based (BEST)** - Most resilient
   ```typescript
   page.getByRole('button', { name: /save/i })
   page.getByRole('heading', { name: /users/i })
   ```

2. **Label-based (GOOD)** - For form inputs
   ```typescript
   page.getByLabel(/email/i)
   page.getByLabel('Password')
   ```

3. **Test ID (ACCEPTABLE)** - When role/label won't work
   ```typescript
   page.getByTestId('user-avatar')
   ```

4. **CSS selectors (LAST RESORT)** - Brittle, avoid
   ```typescript
   page.locator('.user-card .actions button')
   ```

## Barrel Export

Export all page objects from `e2e/pages/index.ts`:

```typescript
// e2e/pages/index.ts

// Auth
export { LoginPage } from './Auth/Login.page'

// App - Users
export { UsersIndexPage } from './App/Users/Index.page'
export { UsersCreatePage } from './App/Users/Create.page'
export { UsersEditPage } from './App/Users/Edit.page'
```

## Index Page Pattern

Index pages (list/table views) should include:

1. **URL ownership** - The page owns its URL pattern
2. **Navigation verification** - Method to verify navigation to this page
3. **List verification** - Methods to check items exist in the list

```typescript
export class UsersIndexPage {
  readonly page: Page
  private readonly urlPattern = /.*\/app\/users/

  constructor(page: Page) {
    this.page = page
  }

  async goto() {
    await this.page.goto('/app/users')
  }

  // Other tests use this after form submission
  async expectNavigatedTo(timeout = 15000) {
    await this.page.waitForURL(this.urlPattern, { timeout })
  }

  // Verify an item appears in the list
  async expectRowVisible(identifier: string) {
    const row = this.page.getByRole('row', { name: new RegExp(identifier, 'i') })
    await expect(row).toBeVisible()
  }

  async expectRowNotVisible(identifier: string) {
    const row = this.page.getByRole('row', { name: new RegExp(identifier, 'i') })
    await expect(row).not.toBeVisible()
  }

  // Get row for further interaction
  getRow(identifier: string): Locator {
    return this.page.getByRole('row', { name: new RegExp(identifier, 'i') })
  }

  async clickEditFor(identifier: string) {
    await this.getRow(identifier).getByRole('link', { name: /edit/i }).click()
  }
}
```

**Why?**
- URL pattern defined once, not scattered across tests
- Tests verify data appears in list, not just "redirect happened"
- Enables clicking edit/delete for specific rows

## What NOT to Create Page Objects For

Only skip page objects for `_components/` directories in Vue - these get component classes, not page objects:

```
Vue _components/              E2E _components/
────────────────────────────────────────────────
Pages/App/Users/_components/  →  pages/App/Users/_components/
└── Form.vue                  →  └── Form.component.ts (NOT .page.ts)
```

## Component Extraction

When a page has complex interactive sections, extract them into component classes. The page object composes these components.

### When to Extract

| Page Element | Extract To | Access Pattern |
|--------------|------------|----------------|
| Data table with columns | `*TableComponent` | `page.table.expectCellValue('Status', 'open')` |
| Form with fields | `*FormComponent` | `page.form.fill({ name: 'John' })` |
| QueryBuilder filters | `QueryBuilderComponent` | `page.filters.addFilter('Status', 'Equals', 'active')` |
| Confirmation modal | `ConfirmModalComponent` | `page.confirmModal.clickConfirm()` |
| Tabs | Methods on page | `page.clickTab('Settings')` |
| Pagination | Methods on page | `page.goToNextPage()` |

**Rule of thumb:** If a section has 3+ interactive elements that work together, extract it.

### Component Composition Pattern

```typescript
// e2e/pages/App/Orders/Index.page.ts
import { QueryBuilderComponent } from '@e2e/components/Filters/QueryBuilder.component'
import { OrdersTableComponent } from './_components/OrdersTable.component'

export class OrdersIndexPage {
  readonly page: Page

  // Composed components
  readonly filters: QueryBuilderComponent
  readonly table: OrdersTableComponent

  // Simple elements stay as locators
  readonly addNewButton: Locator
  readonly exportButton: Locator

  constructor(page: Page) {
    this.page = page
    this.filters = new QueryBuilderComponent(page)
    this.table = new OrdersTableComponent(page)
    this.addNewButton = page.getByRole('button', { name: 'Add new' })
    this.exportButton = page.getByRole('button', { name: 'Export' })
  }
}
```

## Table Component Pattern

Index pages often have tables. Create a table component that allows tests to verify data by column name.

```typescript
// e2e/pages/App/Orders/_components/OrdersTable.component.ts
import { type Locator, type Page, expect } from '@playwright/test'

export class OrdersTableComponent {
  readonly page: Page
  readonly container: Locator
  readonly headers: Locator
  readonly rows: Locator

  constructor(page: Page, container?: Locator) {
    this.page = page
    this.container = container ?? page.locator('table').first()
    this.headers = this.container.locator('thead th')
    this.rows = this.container.locator('tbody tr')
  }

  /**
   * Get the column index for a given header label.
   */
  async getColumnIndex(headerLabel: string): Promise<number> {
    const headerCount = await this.headers.count()
    for (let i = 0; i < headerCount; i++) {
      const text = await this.headers.nth(i).textContent()
      if (text?.toLowerCase().includes(headerLabel.toLowerCase())) {
        return i
      }
    }
    return -1
  }

  /**
   * Assert the table has a specific number of rows.
   */
  async expectRowCount(count: number): Promise<void> {
    await expect(this.rows).toHaveCount(count)
  }

  /**
   * Assert at least one row has a value in a specific column.
   */
  async expectCellValue(columnLabel: string, value: string): Promise<void> {
    const colIndex = await this.getColumnIndex(columnLabel)
    if (colIndex === -1) {
      throw new Error(`Column "${columnLabel}" not found`)
    }

    const rowCount = await this.rows.count()
    for (let i = 0; i < rowCount; i++) {
      const cellText = await this.rows.nth(i).locator('td').nth(colIndex).textContent()
      if (cellText?.toLowerCase().includes(value.toLowerCase())) {
        return // Found it
      }
    }

    throw new Error(`Value "${value}" not found in column "${columnLabel}"`)
  }

  /**
   * Assert NO row has a value in a specific column.
   */
  async expectNoCellValue(columnLabel: string, value: string): Promise<void> {
    const colIndex = await this.getColumnIndex(columnLabel)
    if (colIndex === -1) return // Column doesn't exist, so value can't exist

    const rowCount = await this.rows.count()
    for (let i = 0; i < rowCount; i++) {
      const cellText = await this.rows.nth(i).locator('td').nth(colIndex).textContent()
      if (cellText?.toLowerCase().includes(value.toLowerCase())) {
        throw new Error(`Value "${value}" found in column "${columnLabel}" but should not exist`)
      }
    }
  }

  /**
   * Get all data from a row as key-value object.
   */
  async getRowData(rowIndex: number): Promise<Record<string, string>> {
    const headers = await this.headers.allTextContents()
    const cells = this.rows.nth(rowIndex).locator('td')
    const cellTexts = await cells.allTextContents()

    const data: Record<string, string> = {}
    headers.forEach((header, i) => {
      if (header.trim()) {
        data[header.trim()] = cellTexts[i]?.trim() ?? ''
      }
    })
    return data
  }
}
```

**Usage in tests:**

```typescript
test('filters show only open orders', async ({ authenticatedPage }) => {
  const { page } = authenticatedPage
  const indexPage = new OrdersIndexPage(page)

  await indexPage.goto()
  await indexPage.filters.addAndConfigureFilter('Status', 'Equals', 'open')
  await indexPage.applyFilters()

  // Verify results through the table component
  await indexPage.table.expectRowCount(3)
  await indexPage.table.expectCellValue('Status', 'open')
  await indexPage.table.expectNoCellValue('Status', 'closed')
})
```

## Page Object Completeness Checklist

Before marking a page object "done", verify tests can do everything without raw locators:

### For ALL Pages

- [ ] `goto()` - Navigate to the page
- [ ] `expectLoaded()` - Verify page loaded correctly
- [ ] All buttons/links have action methods (`clickSave()`, `clickCancel()`)
- [ ] All displayed text has assertion methods if tests need to verify it

### For Index/List Pages

- [ ] Table component with column-aware assertions (`expectCellValue`, `expectRowCount`)
- [ ] Filter component if page has filters (`filters.addFilter(...)`)
- [ ] Pagination methods if page has pagination (`goToNextPage()`)
- [ ] Row actions (`clickEditFor(identifier)`, `clickDeleteFor(identifier)`)
- [ ] `expectNavigatedTo()` for post-redirect verification

### For Create/Edit Pages

- [ ] Form component with `fill()` and `submit()` methods
- [ ] All form fields accessible (`form.nameInput`, `form.emailInput`)
- [ ] Validation error assertions (`form.expectError('Email is required')`)
- [ ] Modal components if page has modals

### Ask Yourself

> "If I write a test for this page, will I need ANY raw locators?"
>
> If yes, the page object is incomplete. Add the missing methods/components.
