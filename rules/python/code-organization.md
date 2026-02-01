---
paths: "**/*.py"
---

# Python Code Organization

## Single Class Per File

**One class per file with PascalCase filename matching the class name.**

### Directory Structure Example

```
src/
├── __init__.py              # Re-exports for convenience
├── models/
│   ├── __init__.py
│   ├── User.py              # User class
│   ├── Order.py             # Order class
│   └── Product.py           # Product class
├── services/
│   ├── __init__.py
│   ├── UserService.py       # UserService class
│   └── OrderService.py      # OrderService class
└── utils/
    ├── __init__.py
    └── PathHelper.py        # PathHelper class
```

### ✅ CORRECT - Single Class Per File

```python
# src/models/User.py
from dataclasses import dataclass

@dataclass
class User:
    """Represents a user in the system."""
    id: int
    name: str
    email: str
```

### ❌ WRONG - Multiple Classes Per File

```python
# src/models.py - DON'T DO THIS
@dataclass
class User:
    id: int
    name: str

@dataclass
class Order:
    id: int
    total: float

@dataclass
class Product:
    id: int
    price: float
```

## Package `__init__.py` Exports

Re-export classes for convenient imports:

```python
# src/models/__init__.py
"""Models package."""

from .User import User
from .Order import Order
from .Product import Product

__all__ = ["User", "Order", "Product"]
```

This enables both import styles:

```python
# From package
from src.models import User

# From specific file
from src.models.User import User
```

## Avoid Standard Library Naming Conflicts

**NEVER name modules/packages after Python standard library modules.**

### ❌ WRONG - Conflicts with stdlib

```
src/
├── logging/       # CONFLICTS with stdlib logging!
├── json/          # CONFLICTS with stdlib json!
├── email/         # CONFLICTS with stdlib email!
└── typing/        # CONFLICTS with stdlib typing!
```

This causes errors:
```python
# src/logging/Logger.py
import logging  # Imports YOUR package, not stdlib!

class Logger:
    def __init__(self):
        self._logger = logging.getLogger()  # AttributeError!
```

### ✅ CORRECT - Unique Package Names

```
src/
├── loggers/       # Unique - no conflict
├── serializers/   # Instead of 'json'
├── notifications/ # Instead of 'email'
└── type_utils/    # Instead of 'typing'
```

## Organize by Domain

Group by domain/purpose, not technical layer:

```
# ✅ CORRECT - By domain
src/
├── users/
│   ├── User.py
│   ├── UserService.py
│   └── UserRepository.py
├── orders/
│   ├── Order.py
│   ├── OrderService.py
│   └── OrderRepository.py
└── shared/
    └── BaseRepository.py

# ❌ WRONG - By technical layer only
src/
├── models/        # All models mixed together
├── services/      # All services mixed together
└── repositories/  # All repositories mixed together
```

## Checklist

- [ ] One class per file with matching PascalCase filename?
- [ ] Package name doesn't conflict with stdlib?
- [ ] `__init__.py` exports classes for convenience?
- [ ] Organized by domain/purpose?
