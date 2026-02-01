---
paths: "**/*.py"
---

# Python Type Hints and Dataclasses

## Always Use Type Hints

**Every function parameter and return type should have type hints.**

### ✅ CORRECT - Full Type Hints

```python
from pathlib import Path

class FileProcessor:
    def __init__(self, base_path: Path, verbose: bool = False) -> None:
        self._base_path: Path = base_path
        self._verbose: bool = verbose
        self._cache: dict[str, bytes] | None = None

    def process(self, filename: str) -> bytes:
        path = self._base_path / filename
        return path.read_bytes()

    def process_many(self, filenames: list[str]) -> dict[str, bytes]:
        return {name: self.process(name) for name in filenames}
```

### ❌ WRONG - Missing Type Hints

```python
class FileProcessor:
    def __init__(self, base_path, verbose=False):
        self._base_path = base_path
        self._verbose = verbose
        self._cache = None

    def process(self, filename):
        path = self._base_path / filename
        return path.read_bytes()
```

## Use Dataclasses for Data Models

**Prefer dataclasses over plain classes for data containers.**

### ✅ CORRECT - Dataclass

```python
from dataclasses import dataclass, field
from datetime import datetime

@dataclass
class Order:
    """Represents a customer order."""
    id: int
    customer_id: int
    total: float
    items: list[str] = field(default_factory=list)
    created_at: datetime = field(default_factory=datetime.now)
    notes: str | None = None

    @property
    def is_empty(self) -> bool:
        return len(self.items) == 0

    @property
    def item_count(self) -> int:
        return len(self.items)
```

### ❌ WRONG - Plain Class for Data

```python
class Order:
    def __init__(self, id, customer_id, total, items=None, notes=None):
        self.id = id
        self.customer_id = customer_id
        self.total = total
        self.items = items or []  # Mutable default anti-pattern
        self.created_at = datetime.now()
        self.notes = notes
```

## Modern Type Syntax (Python 3.10+)

Use `|` for unions and built-in generics:

```python
# ✅ CORRECT - Modern syntax
def find_user(user_id: int) -> User | None:
    ...

def get_items() -> list[str]:
    ...

def get_mapping() -> dict[str, int]:
    ...

# ❌ AVOID - Old syntax (verbose)
from typing import Optional, List, Dict, Union

def find_user(user_id: int) -> Optional[User]:
    ...

def get_items() -> List[str]:
    ...
```

## Collection Type Hints

Always specify item types:

```python
# ✅ CORRECT - Item types specified
def get_users() -> list[User]:
    ...

def get_scores() -> dict[str, int]:
    ...

def process(items: set[str]) -> tuple[int, str]:
    ...

# ❌ WRONG - Missing item types
def get_users() -> list:
    ...

def get_scores() -> dict:
    ...
```

## Dataclass Mutable Defaults

Use `field(default_factory=...)` for mutable defaults:

```python
from dataclasses import dataclass, field

@dataclass
class Container:
    # ✅ CORRECT - default_factory for mutables
    items: list[str] = field(default_factory=list)
    metadata: dict[str, str] = field(default_factory=dict)
    tags: set[str] = field(default_factory=set)

    # ❌ WRONG - Mutable default (shared between instances!)
    # items: list[str] = []           # BUG!
    # metadata: dict[str, str] = {}   # BUG!
```

## Abstract Base Classes

Use ABC for interfaces:

```python
from abc import ABC, abstractmethod

class Repository(ABC):
    """Abstract repository interface."""

    @abstractmethod
    def find(self, id: int) -> Model | None:
        """Find by ID."""
        pass

    @abstractmethod
    def save(self, entity: Model) -> Model:
        """Save entity."""
        pass

    def find_or_fail(self, id: int) -> Model:
        """Find by ID or raise. (Concrete method using abstract ones)"""
        entity = self.find(id)
        if entity is None:
            raise EntityNotFoundError(id)
        return entity
```

## TYPE_CHECKING for Imports

Avoid circular imports with TYPE_CHECKING:

```python
from typing import TYPE_CHECKING

if TYPE_CHECKING:
    from .Order import Order
    from .Customer import Customer

class OrderService:
    def create_order(self, customer: "Customer") -> "Order":
        ...
```

## Checklist

- [ ] All parameters have type hints?
- [ ] All return types specified (including `-> None`)?
- [ ] Using dataclasses for data containers?
- [ ] Using `| None` instead of `Optional`?
- [ ] Collections include item types (`list[str]` not `list`)?
- [ ] Using `field(default_factory=...)` for mutable defaults?
- [ ] Using TYPE_CHECKING for forward references?
