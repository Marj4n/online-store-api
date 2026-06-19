# Online Store API

## Requirements

- PHP 8+
- MySQL

## Setup

1. Import sql/schema.sql
2. Configure config/database.php
3. Run server

```bash
php -S localhost:8000
```

## Endpoints

GET /online-store-api/api/products

POST /online-store-api/api/orders

GET /online-store-api/api/orders

## Flash Sale Test

Inventory Product #1 = 10

Run:

```bash
php tests/race_test.php
```

Expected:

SUCCESS : 10
FAILED : 90
Final inventory : 0
