---
paths: "**/*.py"
---

# Python Naming Conventions

## Class Names - Be Specific

**Use domain-specific names. Avoid generic names.**

### ❌ WRONG - Generic Names

```python
class Logger:        # Logger for what?
class Config:        # Config for what?
class Data:          # Data representing what?
class Service:       # Service doing what?
class Handler:       # Handler for what?
class Manager:       # Manager of what?
class Helper:        # Helper for what?
class Processor:     # Processor of what?
class Utils:         # Utils for what?
```

### ✅ CORRECT - Domain-Specific Names

```python
class DatabaseLogger:        # Logs database operations
class ApiConfig:             # API configuration
class UserProfileData:       # User profile information
class PaymentService:        # Handles payments
class WebhookHandler:        # Handles webhooks
class InventoryManager:      # Manages inventory
class DateHelper:            # Helps with dates
class ImageProcessor:        # Processes images
```

## Service Class Naming

Pattern: `[Domain][Action]Service` or `[Domain]Service`

```python
# ✅ CORRECT
class UserAuthenticationService:
class OrderProcessingService:
class EmailNotificationService:
class FileUploadService:

# ❌ WRONG
class AuthService:       # Auth for what?
class ProcessingService: # Processing what?
class NotifyService:     # Notify how?
```

## File Names Match Class Names

PascalCase filename matching class name:

```
✅ CORRECT
UserService.py        → class UserService
OrderRepository.py    → class OrderRepository
EmailNotifier.py      → class EmailNotifier

❌ WRONG
user_service.py       → class UserService    (style mismatch)
service.py            → class UserService    (name mismatch)
utils.py              → class UserService    (unrelated)
```

## Private Members

Underscore prefix for private attributes and methods:

```python
class UserService:
    def __init__(self, repository: UserRepository):
        self._repository = repository     # Private
        self._cache: dict = {}            # Private

    def _validate_user(self, user: User) -> bool:  # Private method
        return user.email is not None

    def create_user(self, data: dict) -> User:     # Public method
        if not self._validate_user(data):
            raise ValueError("Invalid user")
        return self._repository.create(data)
```

## Constants

SCREAMING_SNAKE_CASE for module-level constants:

```python
# ✅ CORRECT
DEFAULT_TIMEOUT_SECONDS = 30
MAX_RETRY_ATTEMPTS = 3
API_BASE_URL = "https://api.example.com"

# ❌ WRONG
defaultTimeout = 30        # camelCase
default_timeout = 30       # Looks like variable
DefaultTimeout = 30        # Looks like class
```

## Boolean Variables and Methods

Use `is_`, `has_`, `can_`, `should_` prefixes:

```python
# ✅ CORRECT
is_active = True
has_permission = user.has_role("admin")
can_edit = document.is_owner(user)
should_retry = attempt < MAX_RETRY_ATTEMPTS

def is_valid(self) -> bool:
def has_expired(self) -> bool:
def can_access(self, resource: Resource) -> bool:

# ❌ WRONG
active = True              # Unclear it's boolean
permission = True          # Noun, not predicate
edit = True                # Verb without prefix
```

## Checklist

- [ ] Class names are domain-specific (not `Config`, `Service`, `Handler`)?
- [ ] Service names follow `[Domain]Service` pattern?
- [ ] File name matches class name in PascalCase?
- [ ] Private members prefixed with underscore?
- [ ] Constants in SCREAMING_SNAKE_CASE?
- [ ] Boolean names use `is_`, `has_`, `can_` prefixes?
