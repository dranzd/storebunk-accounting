# API Reference

## Overview

This document provides detailed information about the classes, methods, and interfaces available in the Storebunk Inventory library based on the Inventory Domain model.

## Namespace

All classes are under the `Dranzd\StorebunkInventory` namespace.

---

## Core Domain Classes

### StockTracker

Manages stock levels and movements at a specific location.

#### Constructor

```php
public function __construct(Location $location)
```

**Parameters:**
- `$location` (Location) - The location where stock is tracked

**Example:**
```php
$warehouse = new Location('WH-001', 'Main Warehouse');
$tracker = new StockTracker($warehouse);
```

#### Methods

##### receive()

```php
public function receive(
    string $sku,
    int $quantity,
    string $reason,
    ?string $reference = null
): void
```

**Description:** Records stock received into inventory.

**Parameters:**
- `$sku` (string) - Stock keeping unit identifier
- `$quantity` (int) - Quantity received
- `$reason` (string) - Reason for receiving (e.g., "Purchase Order #PO-1001")
- `$reference` (string|null) - Optional reference number

**Throws:**
- `InvalidQuantityException` - When quantity is zero or negative
- `InvalidSkuException` - When SKU is invalid

**Emits:** `StockReceived` event

**Example:**
```php
$tracker->receive('SKU-12345', 100, 'Purchase Order #PO-1001', 'PO-1001');
```

##### reserve()

```php
public function reserve(
    ItemId $itemId,
    LocationId $locationId,
    Quantity $quantity,
    string $reservationId,
    Actor $reservedBy
): void
```

**Description:** Reserves stock for orders or allocations.

**Parameters:**
- `$itemId` (ItemId) - Product identifier
- `$locationId` (LocationId) - Location identifier
- `$quantity` (Quantity) - Quantity to reserve
- `$reservationId` (string) - Unique reservation identifier
- `$reservedBy` (Actor) - Who is reserving the stock (user, system, or integration)

**Throws:**
- `\DomainException` - When insufficient stock available

**Emits:** `StockReserved` event

**Example:**
```php
$actor = Actor::user('user-123', 'John Doe', 'tenant-1', 'branch-1');
$stockTracker->reserve(
    ItemId::fromString('product-123'),
    LocationId::fromString('warehouse-1'),
    Quantity::fromInt(20),
    'reservation-456',
    $actor
);
```

##### consume()

```php
public function consume(
    ItemId $itemId,
    LocationId $locationId,
    Quantity $quantity,
    Reason $reason,
    Actor $consumedBy
): void
```

**Description:** Consumes stock using the configured consumption strategy (FIFO/LIFO/Expiry).

**Parameters:**
- `$itemId` (ItemId) - Product identifier
- `$locationId` (LocationId) - Location identifier
- `$quantity` (Quantity) - Quantity to consume
- `$reason` (Reason) - Reason for consumption
- `$consumedBy` (Actor) - Who consumed the stock (user, system, or integration)

**Throws:**
- `\DomainException` - When insufficient stock available

**Emits:** `StockConsumed` event (may emit multiple for multi-stock consumption)

**Example:**
```php
$actor = Actor::user('user-456', 'Jane Smith', 'tenant-1', 'branch-2');
$stockTracker->consume(
    ItemId::fromString('product-123'),
    LocationId::fromString('warehouse-1'),
    Quantity::fromInt(30),
    Reason::fromString('Customer order #12345'),
    $actor
);
```

##### getAvailableStock()

```php
public function getAvailableStock(string $sku): int
```

**Description:** Returns the available (unreserved) stock quantity.

**Returns:** `int` - Available quantity

##### getReservedStock()

```php
public function getReservedStock(string $sku): int
```

**Description:** Returns the reserved stock quantity.

**Returns:** `int` - Reserved quantity

---

### Location

Represents a physical or logical location where inventory is stored.

#### Constructor

```php
public function __construct(
    string $id,
    string $name,
    ?string $type = null
)
```

**Parameters:**
- `$id` (string) - Unique location identifier
- `$name` (string) - Human-readable location name
- `$type` (string|null) - Location type (warehouse, branch, zone, etc.)

**Example:**
```php
$warehouse = new Location('WH-001', 'Main Warehouse', 'warehouse');
$branch = new Location('BR-005', 'Downtown Branch', 'branch');
```

---

### StockCounter

Handles physical stock counting and reconciliation.

#### Methods

##### startCount()

```php
public function startCount(
    array $items,
    string $countedBy,
    string $countType = 'physical'
): CountSession
```

**Description:** Initiates a stock counting session.

**Parameters:**
- `$items` (array) - Array of SKUs to count
- `$countedBy` (string) - User ID performing the count
- `$countType` (string) - Type of count ('physical', 'cycle')

**Returns:** `CountSession` - The counting session object

##### recordCount()

```php
public function recordCount(
    CountSession $session,
    string $sku,
    int $physicalQuantity
): void
```

**Description:** Records the physical count for an item.

##### completeCount()

```php
public function completeCount(CountSession $session): CountResult
```

**Description:** Completes the count and calculates discrepancies.

**Returns:** `CountResult` - Contains discrepancies and adjustment recommendations

**Emits:** `StockCounted` event

---

### StockTransfer

Manages stock transfers between locations.

#### Methods

##### create()

```php
public function create(
    Location $from,
    Location $to,
    array $items,
    string $reason,
    string $requestedBy
): string
```

**Description:** Creates a new transfer request.

**Parameters:**
- `$from` (Location) - Source location
- `$to` (Location) - Destination location
- `$items` (array) - Array of items with SKU and quantity
- `$reason` (string) - Transfer reason
- `$requestedBy` (string) - User requesting transfer

**Returns:** `string` - Transfer ID

**Emits:** `TransferCreated` event

##### dispatch()

```php
public function dispatch(string $transferId, string $dispatchedBy): void
```

**Description:** Marks transfer as dispatched/in-transit.

**Emits:** `TransferDispatched` event

##### receive()

```php
public function receive(
    string $transferId,
    string $receivedBy,
    array $receivedItems
): void
```

**Description:** Receives transfer at destination.

**Emits:** `TransferReceived` event

---

## Valuation Classes

### FIFOValuation

Implements First-In-First-Out costing method.

#### Methods

##### recordPurchase()

```php
public function recordPurchase(
    string $sku,
    int $quantity,
    float $unitCost
): void
```

##### calculateCOGS()

```php
public function calculateCOGS(string $sku, int $quantitySold): float
```

**Description:** Calculates cost of goods sold using FIFO method.

**Returns:** `float` - Total COGS amount

##### getInventoryValue()

```php
public function getInventoryValue(string $sku): float
```

**Description:** Returns current inventory value.

---

### WeightedAverageValuation

Implements weighted average costing method.

#### Methods

##### getAverageCost()

```php
public function getAverageCost(string $sku): float
```

**Description:** Returns the weighted average cost per unit.

---

## Batch & Serial Tracking

### BatchTracker

Manages batch-tracked inventory (expiry dates, lot numbers).

#### Methods

##### receiveBatch()

```php
public function receiveBatch(Batch $batch): void
```

##### getExpiringBatches()

```php
public function getExpiringBatches(int $daysUntilExpiry): array
```

**Description:** Returns batches expiring within specified days.

**Returns:** `array<Batch>` - Array of expiring batches

##### allocateFromBatch()

```php
public function allocateFromBatch(
    string $sku,
    int $quantity,
    string $batchNumber
): void
```

---

### SerialTracker

Manages serialized inventory (unique serial numbers per unit).

#### Methods

##### receiveSerializedItems()

```php
public function receiveSerializedItems(
    string $sku,
    array $serialNumbers
): void
```

##### sellSerialNumber()

```php
public function sellSerialNumber(
    string $sku,
    string $serialNumber,
    string $soldTo
): void
```

##### getSerialHistory()

```php
public function getSerialHistory(string $serialNumber): SerialHistory
```

---

## Value Objects

### Quantity

Immutable value object representing a quantity.

```php
final class Quantity
{
    public function __construct(private int $value) {}
    public function getValue(): int;
    public function add(Quantity $other): Quantity;
    public function subtract(Quantity $other): Quantity;
}
```

### Reason

Immutable value object representing a reason for stock movement.

```php
final class Reason
{
    public function __construct(
        private string $description,
        private ?string $reference = null
    ) {}
    
    public function getDescription(): string;
    public function getReference(): ?string;
}
```

### Actor

Immutable value object representing the entity that initiated a domain action.

```php
final class Actor
{
    // Factory methods
    public static function user(
        string $id,
        string $name,
        string $tenantId,
        ?string $branchId = null,
        ?string $sourceSystem = 'Application'
    ): self;
    
    public static function system(
        string $name,
        string $sourceSystem
    ): self;
    
    public static function integration(
        string $id,
        string $name,
        string $sourceSystem
    ): self;
    
    public static function unknown(): self;
    
    // Getters
    public function getId(): ?string;
    public function getType(): ActorType;
    public function getName(): ?string;
    public function getTenantId(): ?string;
    public function getBranchId(): ?string;
    public function getSourceSystem(): ?string;
    
    // Type checks
    public function isUser(): bool;
    public function isSystem(): bool;
    public function isIntegration(): bool;
    public function isUnknown(): bool;
    
    // Serialization
    public function toArray(): array;
    public static function fromArray(array $array): static;
}
```

**Usage Examples:**
```php
// User actor
$actor = Actor::user('usr-123', 'John Doe', 'tenant-1', 'branch-5');

// System actor (automated process)
$actor = Actor::system('AutoReplenishment', 'InventorySystem');

// Integration actor (external API)
$actor = Actor::integration('api-client-789', 'Shopify', 'ExternalAPI');

// Unknown actor (fallback)
$actor = Actor::unknown();
```

### Batch

Value object representing a batch of inventory.

```php
final class Batch
{
    public function __construct(
        public readonly string $batchNumber,
        public readonly string $sku,
        public readonly int $quantity,
        public readonly \DateTime $manufacturingDate,
        public readonly \DateTime $expiryDate
    ) {}
}
```

---

## Enums

### StockStatus

```php
enum StockStatus: string
{
    case AVAILABLE = 'available';
    case RESERVED = 'reserved';
    case IN_TRANSIT = 'in_transit';
    case DAMAGED = 'damaged';
    case EXPIRED = 'expired';
}
```

---

## Events

All events extend `DomainEvent` and are immutable.

### StockReceived

```php
final class StockReceived extends DomainEvent
{
    public readonly string $sku;
    public readonly int $quantity;
    public readonly string $locationId;
    public readonly string $reason;
    public readonly \DateTimeImmutable $occurredAt;
}
```

### StockAdjusted

```php
final class StockAdjusted extends DomainEvent
{
    public readonly string $sku;
    public readonly int $quantityChange;
    public readonly string $reason;
    public readonly string $adjustedBy;
}
```

### StockTransferred

```php
final class StockTransferred extends DomainEvent
{
    public readonly string $sku;
    public readonly int $quantity;
    public readonly string $fromLocationId;
    public readonly string $toLocationId;
    public readonly string $reason;
}
```

### StockReserved (Reserved)

```php
final class Reserved extends AbstractAggregateEvent
{
    public readonly string $stockId;
    public readonly int $quantity;
    public readonly string $reservationId;
    public readonly Actor $reservedBy;
    public readonly \DateTimeImmutable $occurredAt;
}
```

**Note:** The `reservedBy` field is an `Actor` value object providing rich context about who reserved the stock (user, system, or integration).

### StockConsumed (Consumed)

```php
final class Consumed extends AbstractAggregateEvent
{
    public readonly string $stockId;
    public readonly int $quantity;
    public readonly int $newQuantity;
    public readonly string $reason;
    public readonly Actor $consumedBy;
    public readonly \DateTimeImmutable $occurredAt;
}
```

**Note:** The `consumedBy` field is an `Actor` value object providing audit trail information about who consumed the stock.

---

## Exceptions

### InsufficientStockException

```php
class InsufficientStockException extends InventoryException
{
    public function getAvailableQuantity(): int;
    public function getRequestedQuantity(): int;
}
```

**Thrown when:** Attempting to reserve or transfer more stock than available.

### InvalidQuantityException

```php
class InvalidQuantityException extends InventoryException {}
```

**Thrown when:** Quantity is zero, negative, or otherwise invalid.

### LocationNotFoundException

```php
class LocationNotFoundException extends InventoryException {}
```

**Thrown when:** Referenced location does not exist.

### BatchNotFoundException

```php
class BatchNotFoundException extends InventoryException {}
```

**Thrown when:** Referenced batch number does not exist.

### StockNotReservedException

```php
class StockNotReservedException extends InventoryException {}
```

**Thrown when:** Attempting to release or deduct non-existent reservation.

---

## Interfaces

### StockRepository

```php
interface StockRepository
{
    public function save(Stock $stock): void;
    public function findBySku(string $sku, Location $location): ?Stock;
    public function findAll(Location $location): array;
}
```

### EventPublisher

```php
interface EventPublisher
{
    public function publish(DomainEvent $event): void;
    public function subscribe(string $eventClass, callable $handler): void;
}
```

### ValuationStrategy

```php
interface ValuationStrategy
{
    public function calculateCOGS(string $sku, int $quantity): float;
    public function getInventoryValue(string $sku): float;
}
```

---

## Complete Example

```php
<?php

use Dranzd\StorebunkInventory\StockTracker;
use Dranzd\StorebunkInventory\Location;
use Dranzd\StorebunkInventory\StockCounter;
use Dranzd\StorebunkInventory\Exceptions\InsufficientStockException;

// Setup
$warehouse = new Location('WH-001', 'Main Warehouse');
$tracker = new StockTracker($warehouse);

try {
    // Receive stock
    $tracker->receive('SKU-12345', 100, 'Purchase Order #PO-1001');
    
    // Reserve for order
    $tracker->reserve('SKU-12345', 25, 'Sales Order #SO-2001');
    
    // Check levels
    $available = $tracker->getAvailableStock('SKU-12345'); // 75
    $reserved = $tracker->getReservedStock('SKU-12345');   // 25
    
    // Perform stock count
    $counter = new StockCounter($warehouse);
    $session = $counter->startCount(['SKU-12345'], 'user-123', 'cycle');
    $counter->recordCount($session, 'SKU-12345', 98);
    $result = $counter->completeCount($session);
    
    // Handle discrepancies
    foreach ($result->getDiscrepancies() as $discrepancy) {
        echo "Variance: {$discrepancy->variance}\n";
    }
    
} catch (InsufficientStockException $e) {
    echo "Not enough stock: {$e->getMessage()}\n";
    echo "Available: {$e->getAvailableQuantity()}\n";
}
```

---

## See Also

- [Domain Vision](domain-vision.md) - Business context and domain model
- [Usage Guide](usage.md) - Practical examples and patterns
- [Installation Guide](installation.md) - Setup instructions
- [Contributing Guide](contributing.md) - How to contribute
