# Usage Guide

## Basic Usage

This guide covers the practical usage of the Storebunk Inventory library, organized by domain capabilities.

## Getting Started

First, require the autoloader:

```php
<?php

require 'vendor/autoload.php';

use Dranzd\StorebunkInventory\StockTracker;
use Dranzd\StorebunkInventory\Location;
use Dranzd\StorebunkInventory\StockCounter;
use Dranzd\StorebunkInventory\ValueObjects\Quantity;
use Dranzd\StorebunkInventory\ValueObjects\Reason;
```

## 1. Stock Tracking

### Basic Stock Operations

```php
<?php

// Create a location
$warehouse = new Location('WH-001', 'Main Warehouse');
$tracker = new StockTracker($warehouse);

// Receive stock (from purchase order)
$tracker->receive(
    sku: 'SKU-12345',
    quantity: 100,
    reason: 'Purchase Order #PO-1001',
    reference: 'PO-1001'
);

// Check available stock
$available = $tracker->getAvailableStock('SKU-12345'); // 100

// Reserve stock (for sales order)
$tracker->reserve(
    sku: 'SKU-12345',
    quantity: 25,
    reason: 'Sales Order #SO-2001',
    reference: 'SO-2001'
);

// Now available is reduced
$available = $tracker->getAvailableStock('SKU-12345'); // 75
$reserved = $tracker->getReservedStock('SKU-12345'); // 25
```

### Stock Status Tracking

```php
<?php

use Dranzd\StorebunkInventory\Enums\StockStatus;

// Get stock by status
$availableStock = $tracker->getStockByStatus('SKU-12345', StockStatus::AVAILABLE);
$reservedStock = $tracker->getStockByStatus('SKU-12345', StockStatus::RESERVED);
$inTransitStock = $tracker->getStockByStatus('SKU-12345', StockStatus::IN_TRANSIT);
$damagedStock = $tracker->getStockByStatus('SKU-12345', StockStatus::DAMAGED);

// Mark stock as damaged
$tracker->markAsDamaged(
    sku: 'SKU-12345',
    quantity: 5,
    reason: 'Water damage during storage'
);
```

## 2. Stock Counting & Reconciliation

### Physical Stock Count

```php
<?php

use Dranzd\StorebunkInventory\StockCounter;

$counter = new StockCounter($warehouse);

// Start a stock count
$countSession = $counter->startCount(
    items: ['SKU-12345', 'SKU-67890'],
    countedBy: 'user-123',
    countType: 'physical'
);

// Record counted quantities
$counter->recordCount($countSession, 'SKU-12345', 95);
$counter->recordCount($countSession, 'SKU-67890', 150);

// Complete count and get discrepancies
$result = $counter->completeCount($countSession);

foreach ($result->getDiscrepancies() as $discrepancy) {
    echo "SKU: {$discrepancy->sku}\n";
    echo "System: {$discrepancy->systemQuantity}\n";
    echo "Physical: {$discrepancy->physicalQuantity}\n";
    echo "Variance: {$discrepancy->variance}\n";
}

// Approve adjustments
$counter->approveAdjustments($countSession, 'manager-456');
```

### Cycle Counting

```php
<?php

// Schedule cycle counts for high-value items
$counter->scheduleCycleCount(
    items: ['SKU-HIGH-VALUE-1', 'SKU-HIGH-VALUE-2'],
    frequency: 'weekly',
    assignedTo: 'team-inventory'
);
```

## 3. Stock Movement & Transfer

### Inter-Location Transfers

```php
<?php

use Dranzd\StorebunkInventory\StockTransfer;

$sourceWarehouse = new Location('WH-001', 'Main Warehouse');
$targetBranch = new Location('BR-005', 'Downtown Branch');

$transfer = new StockTransfer();

// Create transfer request
$transferId = $transfer->create(
    from: $sourceWarehouse,
    to: $targetBranch,
    items: [
        ['sku' => 'SKU-12345', 'quantity' => 50],
        ['sku' => 'SKU-67890', 'quantity' => 30],
    ],
    reason: 'Branch replenishment',
    requestedBy: 'user-123'
);

// Mark as in transit
$transfer->dispatch($transferId, 'driver-789');

// Receive at destination
$transfer->receive(
    transferId: $transferId,
    receivedBy: 'user-456',
    receivedItems: [
        ['sku' => 'SKU-12345', 'quantity' => 50],
        ['sku' => 'SKU-67890', 'quantity' => 28], // 2 damaged in transit
    ]
);
```

## 4. Inventory Valuation

### FIFO Costing

```php
<?php

use Dranzd\StorebunkInventory\Valuation\FIFOValuation;

$valuator = new FIFOValuation();

// Record purchases with costs
$valuator->recordPurchase('SKU-12345', 100, 10.00); // 100 units @ $10
$valuator->recordPurchase('SKU-12345', 50, 12.00);  // 50 units @ $12

// Calculate cost of goods sold
$cogs = $valuator->calculateCOGS('SKU-12345', 120); // Sells 120 units
// COGS = (100 × $10) + (20 × $12) = $1,240

// Get current inventory value
$inventoryValue = $valuator->getInventoryValue('SKU-12345');
// Remaining 30 units @ $12 = $360
```

### Weighted Average Costing

```php
<?php

use Dranzd\StorebunkInventory\Valuation\WeightedAverageValuation;

$valuator = new WeightedAverageValuation();

$valuator->recordPurchase('SKU-12345', 100, 10.00);
$valuator->recordPurchase('SKU-12345', 50, 12.00);

// Weighted average = ((100 × $10) + (50 × $12)) / 150 = $10.67
$avgCost = $valuator->getAverageCost('SKU-12345');
```

## 5. Batch & Serial Tracking

### Batch Management

```php
<?php

use Dranzd\StorebunkInventory\BatchTracker;
use Dranzd\StorebunkInventory\ValueObjects\Batch;

$batchTracker = new BatchTracker($warehouse);

// Receive batch-tracked item
$batch = new Batch(
    batchNumber: 'BATCH-2024-001',
    sku: 'SKU-PHARMA-123',
    quantity: 500,
    manufacturingDate: new \DateTime('2024-01-15'),
    expiryDate: new \DateTime('2026-01-15')
);

$batchTracker->receiveBatch($batch);

// Check expiring batches
$expiringBatches = $batchTracker->getExpiringBatches(
    daysUntilExpiry: 90
);

// Sell from specific batch (FEFO - First Expiry First Out)
$batchTracker->allocateFromBatch(
    sku: 'SKU-PHARMA-123',
    quantity: 100,
    batchNumber: 'BATCH-2024-001'
);
```

### Serial Number Tracking

```php
<?php

use Dranzd\StorebunkInventory\SerialTracker;

$serialTracker = new SerialTracker($warehouse);

// Receive serialized items (electronics, appliances)
$serialTracker->receiveSerializedItems(
    sku: 'SKU-LAPTOP-001',
    serialNumbers: ['SN001', 'SN002', 'SN003']
);

// Sell specific serial number
$serialTracker->sellSerialNumber(
    sku: 'SKU-LAPTOP-001',
    serialNumber: 'SN001',
    soldTo: 'customer-789'
);

// Track warranty and service history
$history = $serialTracker->getSerialHistory('SN001');
```

## 6. Warehousing Operations

### Location Management

```php
<?php

use Dranzd\StorebunkInventory\Warehouse\LocationManager;
use Dranzd\StorebunkInventory\Warehouse\Zone;
use Dranzd\StorebunkInventory\Warehouse\Bin;

$locationMgr = new LocationManager($warehouse);

// Define warehouse structure
$zone = new Zone('ZONE-A', 'Receiving Zone');
$aisle = $zone->addAisle('AISLE-01');
$rack = $aisle->addRack('RACK-01');
$shelf = $rack->addShelf('SHELF-01');
$bin = $shelf->addBin('BIN-001');

// Assign stock to specific bin
$locationMgr->assignToBin(
    sku: 'SKU-12345',
    quantity: 100,
    bin: $bin
);

// Find item location
$locations = $locationMgr->findItemLocations('SKU-12345');
```

### Picking Operations

```php
<?php

use Dranzd\StorebunkInventory\Warehouse\PickingManager;

$pickingMgr = new PickingManager($warehouse);

// Create pick list for order
$pickList = $pickingMgr->createPickList(
    orderId: 'SO-2001',
    items: [
        ['sku' => 'SKU-12345', 'quantity' => 10],
        ['sku' => 'SKU-67890', 'quantity' => 5],
    ]
);

// Optimize picking route
$optimizedRoute = $pickingMgr->optimizeRoute($pickList);

// Record picked items
$pickingMgr->recordPick(
    pickListId: $pickList->getId(),
    sku: 'SKU-12345',
    quantity: 10,
    pickedBy: 'user-picker-01'
);
```

## 7. Reordering & Forecasting

### Automatic Reorder Points

```php
<?php

use Dranzd\StorebunkInventory\Reordering\ReorderManager;

$reorderMgr = new ReorderManager();

// Set reorder point
$reorderMgr->setReorderPoint(
    sku: 'SKU-12345',
    reorderLevel: 50,
    reorderQuantity: 200,
    leadTimeDays: 7
);

// Check items needing reorder
$itemsToReorder = $reorderMgr->getItemsBelowReorderPoint();

foreach ($itemsToReorder as $item) {
    // Trigger purchase order creation
    $reorderMgr->createReorderRequest($item);
}
```

### Demand Forecasting

```php
<?php

use Dranzd\StorebunkInventory\Forecasting\DemandForecaster;

$forecaster = new DemandForecaster();

// Forecast based on historical sales
$forecast = $forecaster->forecastDemand(
    sku: 'SKU-12345',
    periodDays: 30,
    historicalMonths: 6
);

echo "Predicted demand: {$forecast->predictedQuantity} units\n";
echo "Confidence level: {$forecast->confidenceLevel}%\n";
echo "Recommended stock level: {$forecast->recommendedStock}\n";
```

## Error Handling

```php
<?php

use Dranzd\StorebunkInventory\Exceptions\InsufficientStockException;
use Dranzd\StorebunkInventory\Exceptions\InvalidQuantityException;
use Dranzd\StorebunkInventory\Exceptions\LocationNotFoundException;

try {
    $tracker->reserve('SKU-12345', 1000, 'Sales Order');
} catch (InsufficientStockException $e) {
    echo "Not enough stock: {$e->getMessage()}\n";
    echo "Available: {$e->getAvailableQuantity()}\n";
    echo "Requested: {$e->getRequestedQuantity()}\n";
} catch (InvalidQuantityException $e) {
    echo "Invalid quantity: {$e->getMessage()}\n";
} catch (LocationNotFoundException $e) {
    echo "Location not found: {$e->getMessage()}\n";
}
```

## Best Practices

### 1. Always Provide Reasons

Every stock movement should have a clear reason for audit trails:

```php
// Good
$tracker->adjust('SKU-12345', -5, 'Damaged during inspection - Ref: INS-001');

// Bad
$tracker->adjust('SKU-12345', -5, 'adjustment');
```

### 2. Use Transactions for Multi-Step Operations

```php
$tracker->beginTransaction();

try {
    $tracker->reserve('SKU-12345', 10, 'SO-001');
    $tracker->reserve('SKU-67890', 5, 'SO-001');
    $tracker->commit();
} catch (\Exception $e) {
    $tracker->rollback();
    throw $e;
}
```

### 3. Implement Event Listeners

```php
use Dranzd\StorebunkInventory\Events\StockReceived;

$tracker->on(StockReceived::class, function($event) {
    // Notify purchasing department
    // Update accounting system
    // Trigger quality inspection
});
```

### 4. Regular Stock Reconciliation

```php
// Schedule regular cycle counts
$counter->scheduleCycleCount(
    items: $highValueItems,
    frequency: 'weekly'
);

// Perform full physical counts quarterly
$counter->schedulePhysicalCount(
    allItems: true,
    frequency: 'quarterly'
);
```

### 5. Monitor Stock Health

```php
use Dranzd\StorebunkInventory\Analytics\StockHealthMonitor;

$monitor = new StockHealthMonitor();

// Check for slow-moving items
$slowMoving = $monitor->getSlowMovingItems(daysSinceLastMovement: 90);

// Check for overstocked items
$overstocked = $monitor->getOverstockedItems();

// Check for stockouts
$stockouts = $monitor->getStockouts();
```

## Integration Examples

### Event-Driven Integration

```php
<?php

use Dranzd\StorebunkInventory\Events\StockReceived;
use Dranzd\StorebunkInventory\Events\StockReserved;

// Listen to inventory events
$eventBus->subscribe(StockReceived::class, function($event) {
    // Update accounting system
    $accounting->recordInventoryIncrease(
        $event->sku,
        $event->quantity,
        $event->unitCost
    );
});

$eventBus->subscribe(StockReserved::class, function($event) {
    // Notify fulfillment system
    $fulfillment->prepareOrder($event->reference);
});
```

### Laravel Integration

```php
<?php

namespace App\Services;

use Dranzd\StorebunkInventory\StockTracker;
use Illuminate\Support\Facades\Event;

class InventoryService
{
    public function __construct(
        private StockTracker $tracker
    ) {}
    
    public function processOrder($order)
    {
        \DB::transaction(function() use ($order) {
            foreach ($order->items as $item) {
                $this->tracker->reserve(
                    $item->sku,
                    $item->quantity,
                    "Order #{$order->id}"
                );
            }
        });
        
        Event::dispatch(new OrderInventoryReserved($order));
    }
}
```

### Symfony Integration

```php
<?php

namespace App\EventSubscriber;

use Dranzd\StorebunkInventory\Events\StockReceived;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class InventorySubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            StockReceived::class => 'onStockReceived',
        ];
    }
    
    public function onStockReceived(StockReceived $event)
    {
        // Handle stock received event
    }
}
```

## Performance Tips

### 1. Batch Operations

```php
// Instead of multiple individual operations
$tracker->batchReceive([
    ['sku' => 'SKU-001', 'quantity' => 100],
    ['sku' => 'SKU-002', 'quantity' => 200],
    ['sku' => 'SKU-003', 'quantity' => 150],
], 'PO-1001');
```

### 2. Use Read Models for Queries

```php
use Dranzd\StorebunkInventory\ReadModels\StockLevelQuery;

// Optimized for read operations
$query = new StockLevelQuery();
$levels = $query->getStockLevels(
    location: $warehouse,
    skus: ['SKU-001', 'SKU-002']
);
```

### 3. Implement Caching

```php
$cache->remember('stock-levels-' . $warehouse->id, 300, function() use ($tracker) {
    return $tracker->getAllStockLevels();
});
```

### 4. Use Async Processing for Events

```php
// Queue heavy operations
$eventBus->subscribeAsync(StockReceived::class, function($event) {
    // This runs in background
    $valuator->recalculateInventoryValue($event->sku);
});
```

## Real-World Scenarios

### Scenario 1: Retail Store Replenishment

```php
// Daily replenishment workflow
$lowStockItems = $reorderMgr->getItemsBelowReorderPoint();

foreach ($lowStockItems as $item) {
    $transfer = $transferMgr->createTransferFromWarehouse(
        sku: $item->sku,
        quantity: $item->reorderQuantity,
        toBranch: $currentBranch,
        priority: $item->stockLevel === 0 ? 'urgent' : 'normal'
    );
}
```

### Scenario 2: E-commerce Order Fulfillment

```php
// Reserve stock when order is placed
$order = $orderService->create($cartItems);

foreach ($order->items as $item) {
    $tracker->reserve(
        $item->sku,
        $item->quantity,
        "Order #{$order->id}"
    );
}

// Release if payment fails
if (!$payment->successful()) {
    $tracker->releaseReservation("Order #{$order->id}");
}

// Deduct when shipped
if ($order->shipped()) {
    $tracker->deductReserved("Order #{$order->id}");
}
```

### Scenario 3: Restaurant Inventory Management

```php
use Dranzd\StorebunkInventory\Recipe\RecipeManager;

$recipeMgr = new RecipeManager();

// Define recipe with ingredients
$recipeMgr->defineRecipe('DISH-PASTA-CARBONARA', [
    ['sku' => 'ING-PASTA', 'quantity' => 0.2],      // 200g
    ['sku' => 'ING-BACON', 'quantity' => 0.1],      // 100g
    ['sku' => 'ING-EGGS', 'quantity' => 2],         // 2 eggs
    ['sku' => 'ING-PARMESAN', 'quantity' => 0.05],  // 50g
]);

// Deduct ingredients when dish is sold
$recipeMgr->consumeRecipe('DISH-PASTA-CARBONARA', 1);
```

## Next Steps

- Review the [Domain Vision](domain-vision.md) for business context
- Check the [API Reference](api-reference.md) for detailed method documentation
- See [Contributing Guide](contributing.md) to help improve this library
