---
paths: "**/*.py"
---

# Python Logging Patterns

## Structured Logging

**Use structured logging with appropriate context.**

### ✅ CORRECT - Logger Per Class

```python
import logging

class OrderService:
    def __init__(self):
        self._logger = logging.getLogger(__name__)

    def create_order(self, customer_id: int, items: list[str]) -> Order:
        self._logger.info(
            "Creating order",
            extra={"customer_id": customer_id, "item_count": len(items)}
        )
        # ...
        self._logger.info("Order created", extra={"order_id": order.id})
        return order
```

### ❌ WRONG - Module-Level Global Logger State

```python
# DON'T use module-level mutable state
_logger = None

def setup_logging():
    global _logger
    _logger = logging.getLogger()

def log_info(message):
    _logger.info(message)  # Relies on global state
```

## Context-Aware Logging Classes

When you need scoped logging, create logger classes that require context:

```python
from abc import ABC, abstractmethod
from pathlib import Path
import logging

class ScopedLogger(ABC):
    """Base class for context-aware loggers."""

    def __init__(self, verbose: bool = False):
        self._verbose = verbose
        self._logger: logging.Logger | None = None

    @property
    @abstractmethod
    def logger_name(self) -> str:
        """Unique name for this logger instance."""
        pass

    def _ensure_initialized(self) -> None:
        if self._logger is not None:
            return
        self._logger = logging.getLogger(self.logger_name)
        # Configure handlers...

    def info(self, message: str) -> None:
        self._ensure_initialized()
        self._logger.info(message)


class RequestLogger(ScopedLogger):
    """Logger scoped to a specific request."""

    def __init__(self, request_id: str, verbose: bool = False):
        super().__init__(verbose)
        self._request_id = request_id

    @property
    def logger_name(self) -> str:
        return f"app.request.{self._request_id}"
```

## Lazy Initialization

Initialize loggers on first use:

```python
class ServiceLogger:
    def __init__(self, service_name: str):
        self._service_name = service_name
        self._logger: logging.Logger | None = None

    def _get_logger(self) -> logging.Logger:
        """Lazy initialization."""
        if self._logger is None:
            self._logger = logging.getLogger(f"services.{self._service_name}")
        return self._logger

    def info(self, message: str) -> None:
        self._get_logger().info(message)
```

## Context Managers for Cleanup

Support automatic cleanup with context managers:

```python
class FileLogger:
    def __init__(self, log_path: Path):
        self._log_path = log_path
        self._handler: logging.FileHandler | None = None
        self._logger: logging.Logger | None = None

    def _setup(self) -> None:
        self._logger = logging.getLogger(f"file.{self._log_path.stem}")
        self._handler = logging.FileHandler(self._log_path)
        self._logger.addHandler(self._handler)

    def close(self) -> None:
        if self._handler:
            self._handler.close()
            if self._logger:
                self._logger.removeHandler(self._handler)

    def __enter__(self) -> "FileLogger":
        self._setup()
        return self

    def __exit__(self, *args) -> None:
        self.close()


# Usage
with FileLogger(Path("app.log")) as logger:
    logger.info("Processing started")
    # ...
# Automatically cleaned up
```

## Unique Logger Names

Prevent conflicts with unique hierarchical names:

```python
# ✅ CORRECT - Unique names
logging.getLogger("myapp.services.user")
logging.getLogger("myapp.services.order")
logging.getLogger(f"myapp.request.{request_id}")

# ❌ WRONG - Generic names that conflict
logging.getLogger("service")      # Which service?
logging.getLogger("request")      # Which request?
logging.getLogger("logger")       # Meaningless
```

## Log Levels Usage

Use appropriate levels:

```python
logger.debug("Cache hit for key: %s", key)           # Detailed debugging
logger.info("Order created: %s", order_id)           # Normal operations
logger.warning("Rate limit approaching: %d/100", n)  # Potential issues
logger.error("Payment failed: %s", error)            # Errors (recoverable)
logger.exception("Unexpected error")                  # Errors with traceback
logger.critical("Database connection lost")          # System failures
```

## Checklist

- [ ] One logger per class (not global module state)?
- [ ] Logger names are unique and hierarchical?
- [ ] Using lazy initialization?
- [ ] Context managers for cleanup when using file handlers?
- [ ] Appropriate log levels?
- [ ] Structured data in `extra` parameter?
