---
paths: "**/*.py"
---

# Python Service Patterns

## Separate Data from Behavior

**Models hold data. Services contain business logic.**

### ✅ CORRECT - Separated Concerns

```python
# models/Order.py - Pure data
@dataclass
class Order:
    """Data container for order information."""
    id: int
    customer_id: int
    items: list[str]
    total: float

    @property
    def is_empty(self) -> bool:
        return len(self.items) == 0


# services/OrderService.py - Business logic
class OrderService:
    """Service for order operations."""

    def __init__(self, repository: OrderRepository, notifier: Notifier):
        self._repository = repository
        self._notifier = notifier

    def place_order(self, order: Order) -> Order:
        """Place an order and notify customer."""
        saved = self._repository.save(order)
        self._notifier.send_confirmation(saved)
        return saved
```

### ❌ WRONG - Mixed Concerns

```python
@dataclass
class Order:
    id: int
    customer_id: int
    items: list[str]

    def save(self) -> None:              # I/O in data class!
        db.save(self)

    def send_confirmation(self) -> None:  # Side effects!
        email.send(f"Order {self.id} confirmed")

    def to_pdf(self) -> bytes:            # Business logic!
        return pdf_generator.create(self)
```

## Dependency Injection

**Pass dependencies through constructor, not hard-coded.**

### ✅ CORRECT - Injected Dependencies

```python
class UserService:
    def __init__(
        self,
        repository: UserRepository,
        hasher: PasswordHasher,
        notifier: EmailNotifier,
    ):
        self._repository = repository
        self._hasher = hasher
        self._notifier = notifier

    def register(self, email: str, password: str) -> User:
        hashed = self._hasher.hash(password)
        user = self._repository.create(email=email, password=hashed)
        self._notifier.send_welcome(user)
        return user


# Easy to test
def test_register():
    mock_repo = Mock(spec=UserRepository)
    mock_hasher = Mock(spec=PasswordHasher)
    mock_notifier = Mock(spec=EmailNotifier)

    service = UserService(mock_repo, mock_hasher, mock_notifier)
    service.register("test@example.com", "password")

    mock_repo.create.assert_called_once()
```

### ❌ WRONG - Hard-coded Dependencies

```python
class UserService:
    def __init__(self):
        # Hard-coded - impossible to test without real database!
        self._repository = UserRepository()
        self._hasher = BcryptHasher()
        self._notifier = SmtpNotifier()
```

## Factory Methods

Use class methods for alternative construction:

```python
class DatabaseConnection:
    def __init__(self, host: str, port: int, database: str):
        self._host = host
        self._port = port
        self._database = database

    @classmethod
    def from_url(cls, url: str) -> "DatabaseConnection":
        """Create from connection URL."""
        parsed = urlparse(url)
        return cls(
            host=parsed.hostname,
            port=parsed.port or 5432,
            database=parsed.path.lstrip("/"),
        )

    @classmethod
    def from_env(cls) -> "DatabaseConnection":
        """Create from environment variables."""
        return cls(
            host=os.environ["DB_HOST"],
            port=int(os.environ.get("DB_PORT", 5432)),
            database=os.environ["DB_NAME"],
        )


# Multiple ways to construct
conn1 = DatabaseConnection("localhost", 5432, "mydb")
conn2 = DatabaseConnection.from_url("postgres://localhost/mydb")
conn3 = DatabaseConnection.from_env()
```

## Focused Methods

Each method should do one thing:

```python
class OrderService:
    # ✅ CORRECT - Focused methods

    def find_order(self, order_id: int) -> Order | None:
        """Find order by ID."""
        return self._repository.find(order_id)

    def create_order(self, data: OrderData) -> Order:
        """Create new order."""
        return self._repository.create(data)

    def find_or_create(self, order_id: int, data: OrderData) -> Order:
        """Find existing or create new."""
        existing = self.find_order(order_id)
        if existing:
            return existing
        return self.create_order(data)

    # ❌ WRONG - Method doing too much
    def handle_order(self, order_id: int | None, data: dict) -> Order:
        """Find or create, validate, save, notify, and return."""
        # Too many responsibilities!
```

## Static Methods for Pure Functions

Use `@staticmethod` for functions without instance state:

```python
class PriceCalculator:
    def __init__(self, tax_rate: float):
        self._tax_rate = tax_rate

    def calculate_total(self, subtotal: float) -> float:
        """Calculate total with tax (uses instance state)."""
        return subtotal + self.calculate_tax(subtotal)

    def calculate_tax(self, amount: float) -> float:
        """Calculate tax (uses instance state)."""
        return amount * self._tax_rate

    @staticmethod
    def format_currency(amount: float) -> str:
        """Format as currency (no instance state needed)."""
        return f"${amount:,.2f}"

    @staticmethod
    def round_to_cents(amount: float) -> float:
        """Round to nearest cent (no instance state needed)."""
        return round(amount, 2)
```

## Service Lifecycle

Keep services stateless or with minimal, well-defined state:

```python
# ✅ CORRECT - Stateless service
class EmailService:
    def __init__(self, smtp_client: SmtpClient):
        self._client = smtp_client  # Injected dependency, not state

    def send(self, to: str, subject: str, body: str) -> None:
        self._client.send(to, subject, body)


# ✅ CORRECT - Service with cache (well-defined state)
class ConfigService:
    def __init__(self, loader: ConfigLoader):
        self._loader = loader
        self._cache: Config | None = None  # Clearly defined cache

    def get(self, force_reload: bool = False) -> Config:
        if self._cache is None or force_reload:
            self._cache = self._loader.load()
        return self._cache


# ❌ WRONG - Service accumulating unclear state
class ProcessingService:
    def __init__(self):
        self._items_processed = []    # Why keep this?
        self._last_error = None       # Confusing lifecycle
        self._is_running = False      # Service shouldn't track this
```

## Checklist

- [ ] Data classes contain only data and simple properties?
- [ ] Business logic is in services, not data classes?
- [ ] Dependencies injected through constructor?
- [ ] Methods are focused (single responsibility)?
- [ ] Factory methods for complex/alternative construction?
- [ ] Static methods for pure functions?
- [ ] Stateless or minimal, well-defined state?
