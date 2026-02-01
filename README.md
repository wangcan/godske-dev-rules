# Godske Dev Rules

Development convention rules for Claude Code. Provides reusable coding conventions for PHP, Laravel, Vue, TypeScript, Python, and more.

## Installation

```bash
composer require rasmusgodske/godske-dev-rules --dev
```

## Usage

After installation, run the artisan command to sync rules to your project:

```bash
php artisan dev-rules:update
```

This copies the rules to `.claude/rules/techstack/` in your project.

### Options

```bash
# Update rules (overwrites existing)
php artisan dev-rules:update --force

# Custom installation path
php artisan dev-rules:update --path=.claude/rules/custom
```

## Rule Categories

| Category | Path | Description |
|----------|------|-------------|
| Backend | `backend/` | PHP and Laravel conventions |
| Frontend | `frontend/` | Vue 3 and TypeScript conventions |
| Data Classes | `dataclasses/` | Spatie Laravel Data patterns |
| E2E | `e2e/` | Playwright testing conventions |
| Principles | `principles/` | Cross-cutting development principles |
| Python | `python/` | Python development conventions |

## How Rules Work

Rules are markdown files that Claude Code automatically loads based on file paths. Each rule has YAML frontmatter specifying when it should load:

```yaml
---
paths: app/**/*.php
---

# PHP Conventions

Your conventions here...
```

When you edit a `.php` file, rules matching `app/**/*.php` are loaded automatically.

## Directory Structure

After running `dev-rules:update`, your project will have:

```
.claude/rules/
├── techstack/           # Rules from this package (synced)
│   ├── backend/
│   ├── frontend/
│   ├── dataclasses/
│   ├── e2e/
│   ├── principles/
│   └── python/
└── project/             # Your custom rules (not synced)
    ├── backend/
    └── frontend/
```

## Custom Rules

Add project-specific rules to `.claude/rules/project/`. These are NOT overwritten when running `dev-rules:update`.

## Updating Rules

When a new version of this package is released:

```bash
composer update rasmusgodske/godske-dev-rules
php artisan dev-rules:update --force
```

## Contributing

See [CONTRIBUTING.md](rules/CONTRIBUTING.md) for guidelines on adding or modifying rules.

## Migration from laravel-vue-rules

If you were using `rasmusgodske/laravel-vue-rules`:

1. Remove the old package:
   ```bash
   composer remove rasmusgodske/laravel-vue-rules
   ```

2. Install the new package:
   ```bash
   composer require rasmusgodske/godske-dev-rules --dev
   ```

3. Update your rules:
   ```bash
   php artisan dev-rules:update --force
   ```

The new package includes all the same rules plus Python conventions.

## License

MIT
