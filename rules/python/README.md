# Python Development Rules

Generic Python development conventions. These rules are auto-loaded based on file paths.

## Files

| File | Description | Auto-loaded for |
|------|-------------|-----------------|
| `code-organization.md` | Single class per file, directory structure | `**/*.py` |
| `naming-conventions.md` | Class, module, and variable naming | `**/*.py` |
| `type-hints.md` | Type annotations and dataclasses | `**/*.py` |
| `logging-patterns.md` | Structured logging patterns | `**/*.py` |
| `service-patterns.md` | Service class patterns | `**/*.py` |

## Key Principles

1. **Single Class Per File** - One class per file with matching PascalCase filename
2. **Domain-Specific Names** - Avoid generic names that could conflict or confuse
3. **Avoid Standard Library Conflicts** - Never name modules after stdlib modules
4. **Type Everything** - Use type hints, dataclasses, and proper annotations
5. **Separate Data from Behavior** - Models hold data, services contain logic
6. **Dependency Injection** - Pass dependencies through constructors
