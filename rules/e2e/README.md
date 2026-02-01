---
paths: e2e/**/*.ts
---

# E2E Testing Rules (Playwright)

These rules are **automatically loaded** when working on Playwright E2E test files.

## Available Rules

| Rule File | Loaded For | Covers |
|-----------|------------|--------|
| `data-class-organization.md` | `app/Data/Controllers/E2E/**/*.php` | E2E Data class structure and naming |
| `fixture-organization.md` | `e2e/fixtures/**/*.ts` | E2E fixture/helper structure |
| `database-setup.md` | `e2e/**/*.ts` | SQLite isolation, .env.e2e, server script |
| `smoke-tests.md` | `e2e/tests/**/*.ts` | Console error collection, smoke test pattern |
| `test-conventions.md` | `e2e/tests/**/*.ts` | Test structure, fixtures, assertions |
| `test-data.md` | `e2e/**/*.ts` | Test data management via API |
| `page-objects.md` | `e2e/pages/**/*.ts` | Page Object Model pattern |
| `e2e-components.md` | `e2e/components/**/*.ts` | Global reusable component classes |

## Directory Structure

```
e2e/
├── components/                  # Global component classes (mirrors resources/js/Components/)
│   ├── Filters/
│   │   └── QueryBuilder.component.ts
│   └── Modals/
│       └── ConfirmModal.component.ts
├── docs/                        # Documentation
├── fixtures/                    # Custom Playwright fixtures
│   ├── api/                     # E2E API clients
│   │   ├── BaseE2EApi.ts        # Shared HTTP patterns
│   │   └── index.ts             # Barrel export
│   ├── core/
│   │   └── TestDataOrchestrator.ts # High-level test setup
│   ├── base.fixture.ts          # Extended test with authenticatedPage
│   ├── test-data.ts             # Public API exports
│   └── console-errors.ts        # Console error collection utility
├── pages/                       # Page Object Models (mirrors resources/js/Pages/)
│   ├── _base/                   # Base classes
│   ├── _shared/                 # Layout components
│   ├── App/
│   │   └── Users/
│   │       ├── _components/     # Page-specific components
│   │       ├── Index.page.ts
│   │       └── Create.page.ts
│   └── index.ts                 # Barrel export
├── scripts/
│   └── e2e-server.sh            # Server startup script
├── tests/
│   ├── routes/                  # Route-based tests (mirror URL routes)
│   │   ├── app/                 # /app/* routes
│   │   └── auth/                # /auth/* routes
│   ├── flows/                   # Multi-page user journeys (future)
│   └── components/              # Shared component tests (future)
└── README.md                    # Project-specific quick start
```

## Key Concepts

### 1. Isolated Database

E2E tests use a **separate SQLite database**, not your development database:
- Fresh database per test run (`migrate:fresh`)
- No interference with dev data
- See `database-setup.md` for details

### 2. Test Data via API

Tests create data through `/e2e/*` API endpoints (local env only):
- Uses Laravel factories
- Automatic cleanup after tests
- See `test-data.md` for patterns

### 3. Custom Fixtures

| Fixture | Purpose |
|---------|---------|
| `page` | Raw Playwright page (no auth) |
| `authenticatedPage` | Logged-in page with test data |
| `testData` | Test data without login |
| `testDataHelper` | Manual test data control |

### 4. Smoke Tests

Every page should have smoke tests that verify:
- Page loads without console errors
- Basic content is visible
- See `smoke-tests.md` for pattern

## Quick Example

```typescript
import { test, expect } from '../../../../fixtures/base.fixture'
import { createConsoleErrorCollector } from '../../../../fixtures/console-errors'

test.describe('Dashboard', () => {
  test('page loads without console errors', async ({ authenticatedPage }) => {
    const { page } = authenticatedPage
    const errorCollector = createConsoleErrorCollector(page)

    await page.goto('/app/dashboard')
    await page.waitForLoadState('networkidle')

    expect(errorCollector.getErrors()).toEqual([])
  })
})
```

## Environment Configuration

E2E tests use `.env.e2e` for isolated settings:

| Setting | Value | Purpose |
|---------|-------|---------|
| `APP_ENV` | local | Enable testing routes |
| `APP_PORT` | 8081 | Avoid conflict with dev server (8080) |
| `DB_CONNECTION` | testing | Use SQLite database |
| `DEBUGBAR_ENABLED` | false | Prevent console noise |
| `TELESCOPE_ENABLED` | false | Reduce overhead |
| `QUEUE_CONNECTION` | sync | Immediate processing |

## Running Tests

```bash
npm run e2e              # Run all tests
npm run e2e:ui           # Interactive UI mode
npm run e2e:headed       # See browser
npm run e2e:debug        # Debug mode
```

## Key Conventions

| Aspect | Convention |
|--------|------------|
| **Route tests** | `e2e/tests/routes/` mirrors URL routes exactly |
| **Directory names** | Match route segments exactly (e.g., `user_settings/`) |
| **Test files** | kebab-case, feature-focused names |
| **Smoke tests** | Every route needs `smoke.spec.ts` |
| **Auth tests** | Use `authenticatedPage` fixture |
| **No-auth tests** | Use `page` fixture directly |
| **Port** | 8081 (E2E), 8080 (dev) |
