---
paths: e2e/**/*.ts
---

# E2E Test Data Management

Test data is managed via dedicated E2E API endpoints that are **only available in local/testing environments**.

## Architecture Overview

E2E tests create and clean up data through dedicated E2E API endpoints using **API clients** (`*E2EApi` classes):

```
Test                              Backend
┌──────────────────────┐         ┌─────────────────────────────────┐
│ Playwright           │         │  app/Http/Controllers/E2E/      │
│                      │ HTTP    │  ├── TeamController             │
│ TeamE2EApi           │────────►│  ├── UserController             │
│ UserE2EApi           │         │  ├── TeamMemberController       │
│ TeamMemberE2EApi     │◄────────│  └── SettingsController         │
└──────────────────────┘         └─────────────────────────────────┘
```

**Benefits:**
- **Test isolation** - Each test creates its own data
- **Parallel execution** - Tests don't interfere with each other
- **Clean state** - No leftover data between runs
- **Type safety** - Uses auto-generated TypeScript types from Laravel Data classes
- **RESTful** - One resource per controller, independent operations

## Frontend API Client Structure

### Directory Layout

```
e2e/fixtures/
├── api/                         # E2E API clients (flat structure)
│   ├── BaseE2EApi.ts            # Shared HTTP patterns
│   ├── TeamE2EApi.ts            # Team resource
│   ├── UserE2EApi.ts            # User resource
│   ├── TeamMemberE2EApi.ts      # TeamMember pivot resource
│   ├── SettingsE2EApi.ts        # Settings resource
│   └── index.ts                 # Barrel export
├── core/
│   └── TestDataOrchestrator.ts  # High-level test setup
├── test-data.ts                 # Public API exports
└── base.fixture.ts              # Playwright fixture definitions
```

### BaseE2EApi (Shared Patterns)

All API clients extend `BaseE2EApi` for consistent HTTP handling:

```typescript
// e2e/fixtures/api/BaseE2EApi.ts
import { APIRequestContext } from '@playwright/test'

export class BaseE2EApi {
  constructor(
    protected readonly request: APIRequestContext,
    protected readonly baseURL: string
  ) {}

  protected async post<T>(path: string, data?: unknown): Promise<T> {
    const response = await this.request.post(`${this.baseURL}${path}`, {
      data,
      headers: { 'Content-Type': 'application/json' },
    })

    if (!response.ok()) {
      const text = await response.text()
      throw new Error(`POST ${path} failed: ${response.status()} - ${text}`)
    }

    return response.json() as Promise<T>
  }

  protected async delete(path: string): Promise<void> {
    const response = await this.request.delete(`${this.baseURL}${path}`)

    if (!response.ok()) {
      const text = await response.text()
      throw new Error(`DELETE ${path} failed: ${response.status()} - ${text}`)
    }
  }
}
```

### Resource-Specific API Client

Each resource has its own API client using **generated TypeScript types**:

```typescript
// e2e/fixtures/api/TeamE2EApi.ts
import { BaseE2EApi } from './BaseE2EApi'
import type { App } from '@/types/generated'

type CreateTeamResponse = App.Data.Controllers.E2E.TeamController.CreateTeamResponseData

export class TeamE2EApi extends BaseE2EApi {
  async create(options?: { name?: string }): Promise<CreateTeamResponse> {
    return this.post<CreateTeamResponse>('/e2e/teams', {
      name: options?.name,
    })
  }

  async delete(teamId: number): Promise<void> {
    return super.delete(`/e2e/teams/${teamId}`)
  }
}
```

### TestDataOrchestrator (High-Level Setup)

For common test scenarios, the orchestrator provides convenience methods:

```typescript
// e2e/fixtures/core/TestDataOrchestrator.ts
import { APIRequestContext } from '@playwright/test'
import {
  TeamE2EApi,
  UserE2EApi,
  TeamMemberE2EApi,
  SettingsE2EApi,
} from '../api'

export class TestDataOrchestrator {
  public readonly teams: TeamE2EApi
  public readonly users: UserE2EApi
  public readonly teamMembers: TeamMemberE2EApi
  public readonly settings: SettingsE2EApi

  constructor(request: APIRequestContext, baseURL: string) {
    this.teams = new TeamE2EApi(request, baseURL)
    this.users = new UserE2EApi(request, baseURL)
    this.teamMembers = new TeamMemberE2EApi(request, baseURL)
    this.settings = new SettingsE2EApi(request, baseURL)
  }

  /**
   * Create complete test setup: Team, User, and TeamMember link.
   */
  async createAuthenticatedUser(options?: {
    teamName?: string
    userName?: string
    roleId?: number
  }) {
    // Create independent resources
    const team = await this.teams.create({ name: options?.teamName })
    const user = await this.users.create({ name: options?.userName })

    // Link them via pivot
    const teamMember = await this.teamMembers.create({
      teamId: team.id,
      userId: user.id,
      roleId: options?.roleId,
    })

    return { team, user, teamMember }
  }
}
```

## Playwright Fixtures

### Available Fixtures

```typescript
// e2e/fixtures/base.fixture.ts
import { test as base } from '@playwright/test'
import { TestDataOrchestrator } from './core/TestDataOrchestrator'

export const test = base.extend<{
  testDataHelper: TestDataOrchestrator
  testData: { team: TeamResponse; user: UserResponse; teamMember: TeamMemberResponse }
  authenticatedPage: { page: Page; testData: TestData }
}>({
  // ... fixture implementations
})
```

### `authenticatedPage` (Recommended)

For most tests - provides logged-in page with test data:

```typescript
import { test, expect } from '../../fixtures/base.fixture'

test('user sees dashboard', async ({ authenticatedPage }) => {
  const { page, testData } = authenticatedPage

  // page is already logged in
  await expect(page.getByText(testData.team.name)).toBeVisible()
})
```

### `testData` (No Login)

When you need data but want to handle login yourself:

```typescript
test('login flow', async ({ page, testData }) => {
  await page.goto('/login')
  await page.getByLabel(/email/i).fill(testData.user.email)
  await page.getByLabel(/password/i).fill(testData.user.password)
  await page.getByRole('button', { name: /log in/i }).click()

  await expect(page).toHaveURL(/.*\/dashboard/)
})
```

### `testDataHelper` (Manual Control)

For complex scenarios requiring fine-grained control:

```typescript
test('can link user to multiple teams', async ({ page, testDataHelper }) => {
  // Create resources independently
  const team1 = await testDataHelper.teams.create({ name: 'Team A' })
  const team2 = await testDataHelper.teams.create({ name: 'Team B' })
  const user = await testDataHelper.users.create({ name: 'Multi-team User' })

  // Link user to both teams
  await testDataHelper.teamMembers.create({
    teamId: team1.id,
    userId: user.id,
  })
  await testDataHelper.teamMembers.create({
    teamId: team2.id,
    userId: user.id,
  })

  // Test multi-team behavior...
})
```

## Backend Controller Structure

### Routes (`routes/e2e.php`)

```php
<?php

use App\Http\Controllers\E2E\TeamController;
use App\Http\Controllers\E2E\UserController;
use App\Http\Controllers\E2E\TeamMemberController;
use App\Http\Controllers\E2E\SettingsController;
use Illuminate\Support\Facades\Route;

Route::prefix('e2e')->group(function () {
    // Teams (standalone resource)
    Route::post('/teams', [TeamController::class, 'store']);
    Route::delete('/teams/{team}', [TeamController::class, 'destroy']);

    // Users (standalone resource)
    Route::post('/users', [UserController::class, 'store']);
    Route::delete('/users/{user}', [UserController::class, 'destroy']);

    // TeamMembers (pivot resource - links Team and User)
    Route::post('/team-members', [TeamMemberController::class, 'store']);
    Route::delete('/team-members/{teamMember}', [TeamMemberController::class, 'destroy']);

    // Settings (nested under team/role)
    Route::put('/teams/{team}/roles/{role}/settings', [SettingsController::class, 'update']);
});
```

### Controller Example

```php
<?php

namespace App\Http\Controllers\E2E;

use App\Data\Controllers\E2E\UserController\CreateUserRequestData;
use App\Data\Controllers\E2E\UserController\CreateUserResponseData;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UserController extends Controller
{
    public function store(Request $request): Response
    {
        $data = CreateUserRequestData::validateAndCreate($request->all());

        $user = User::factory()->create([
            'name' => $data->name ?? 'E2E Test User',
            'email' => $data->email ?? 'e2e-' . Str::random(12) . '@test.example.com',
            'password' => bcrypt('password'),
        ]);

        return CreateUserResponseData::from([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'password' => 'password',  // Plain text for tests
        ])->toResponse($request)->setStatusCode(201);
    }

    public function destroy(User $user): Response
    {
        $user->forceDelete();

        return response()->noContent();
    }
}
```

### Data Classes

Use Laravel Data classes with `#[TypeScript]` for auto-generated frontend types:

```php
<?php

namespace App\Data\Controllers\E2E\UserController;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class CreateUserResponseData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public string $password,
    ) {}
}
```

Regenerate types after changes:

```bash
php artisan typescript:transform
```

## Key Principles

1. **Environment Guard** - E2E routes must NOT exist in production
2. **One Resource Per Controller** - RESTful, independent resources
3. **Use Factories** - Leverage Laravel factories for realistic data
4. **Generated Types** - Use auto-generated TypeScript types, never duplicate
5. **Automatic Cleanup** - Fixtures clean up after each test
6. **Plain Text Password** - Return password in response for login tests
7. **Extend BaseE2EApi** - All API clients inherit shared HTTP patterns

## Adding a New Resource

Follow the existing pattern:

1. Create backend controller at `app/Http/Controllers/E2E/{Resource}Controller.php`
2. Create Data classes at `app/Data/Controllers/E2E/{Resource}Controller/`
3. Add routes to `routes/e2e.php`
4. Run `php artisan typescript:transform`
5. Create frontend API client at `e2e/fixtures/api/{Resource}E2EApi.ts`
6. Export from `e2e/fixtures/api/index.ts`
7. Optionally add to `TestDataOrchestrator`

For detailed steps, see `fixture-organization.md`.
