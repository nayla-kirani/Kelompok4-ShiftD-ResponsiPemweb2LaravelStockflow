```php
<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\PurchaseOrderController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\StockMovementController;
use App\Http\Controllers\Api\SupplierController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
| Register and login are public and rate-limited.
| Logout and current-user routes require authentication.
*/

Route::prefix('auth')->group(function () {
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });
});

/*
|--------------------------------------------------------------------------
| Protected Routes
|--------------------------------------------------------------------------
| All routes in this group require Sanctum authentication.
*/

Route::middleware('auth:sanctum')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    Route::get('dashboard', [DashboardController::class, 'index']);

    /*
    |--------------------------------------------------------------------------
    | Categories
    |--------------------------------------------------------------------------
    | All authenticated users can read.
    | Admin and manager can create, update, and delete.
    */

    Route::apiResource('categories', CategoryController::class)
        ->only(['index', 'show']);

    Route::middleware('role:admin,manager')->group(function () {
        Route::apiResource('categories', CategoryController::class)
            ->except(['index', 'show']);
    });

    /*
    |--------------------------------------------------------------------------
    | Suppliers
    |--------------------------------------------------------------------------
    | All authenticated users can read.
    | Admin and manager can create, update, and delete.
    */

    Route::apiResource('suppliers', SupplierController::class)
        ->only(['index', 'show']);

    Route::middleware('role:admin,manager')->group(function () {
        Route::apiResource('suppliers', SupplierController::class)
            ->except(['index', 'show']);
    });

    /*
    |--------------------------------------------------------------------------
    | Products
    |--------------------------------------------------------------------------
    | All authenticated users can read.
    | Admin and manager can manage products and adjust stock.
    */

    Route::get('products/low-stock', [ProductController::class, 'lowStock']);

    Route::apiResource('products', ProductController::class)
        ->only(['index', 'show']);

    Route::middleware('role:admin,manager')->group(function () {
        Route::apiResource('products', ProductController::class)
            ->except(['index', 'show']);

        Route::post(
            'products/{product}/adjust-stock',
            [ProductController::class, 'adjustStock'],
        );
    });

    /*
    |--------------------------------------------------------------------------
    | Purchase Orders
    |--------------------------------------------------------------------------
    | All authenticated users can read and create.
    | Admin and manager can update orders and their status.
    | Only admin can delete orders.
    */

    Route::get('purchase-orders', [PurchaseOrderController::class, 'index']);
    Route::get('purchase-orders/{purchase_order}', [PurchaseOrderController::class, 'show']);
    Route::post('purchase-orders', [PurchaseOrderController::class, 'store']);

    Route::middleware('role:admin,manager')->group(function () {
        Route::match(
            ['put', 'patch'],
            'purchase-orders/{purchase_order}',
            [PurchaseOrderController::class, 'update'],
        );

        Route::match(
            ['put', 'patch'],
            'purchase-orders/{purchase_order}/status',
            [PurchaseOrderController::class, 'updateStatus'],
        );
    });

    Route::delete(
        'purchase-orders/{purchase_order}',
        [PurchaseOrderController::class, 'destroy'],
    )->middleware('role:admin');

    /*
    |--------------------------------------------------------------------------
    | Stock Movements
    |--------------------------------------------------------------------------
    | All authenticated users can view stock movement records.
    */

    Route::get('stock-movements', [StockMovementController::class, 'index']);

    Route::get(
        'stock-movements/product/{productId}',
        [StockMovementController::class, 'byProduct'],
    );

    Route::get(
        'products/{productId}/movements',
        [StockMovementController::class, 'byProduct'],
    );

    /*
    |--------------------------------------------------------------------------
    | Reports
    |--------------------------------------------------------------------------
    | Only admin and manager can access reports.
    */

    Route::prefix('reports')
        ->middleware('role:admin,manager')
        ->controller(ReportController::class)
        ->group(function () {
            Route::get('/', 'combined');
            Route::get('inventory-summary', 'inventorySummary');
            Route::get('stock-movements', 'stockMovements');
            Route::get('top-products', 'topProducts');
            Route::get('purchase-orders', 'purchaseOrders');
        });
});
```