# 🔩 Hardware Shop Inventory Management System

An **enterprise-grade** Inventory Management System built for hardware shops. It handles the full business workflow — from purchasing stock to selling products — with role-based access control, GST-compliant invoicing, audit trails, and detailed reporting.

---

## 📋 Table of Contents

- [Features](#features)
- [Tech Stack](#tech-stack)
- [Project Structure](#project-structure)
- [Database Schema](#database-schema)
- [Roles & Permissions](#roles--permissions)
- [Prerequisites](#prerequisites)
- [Installation](#installation)
- [Running the Application](#running-the-application)
- [Modules Overview](#modules-overview)
- [Reports & Exports](#reports--exports)
- [Audit Trail](#audit-trail)

---

## ✨ Features

- 🔐 **Role-Based Access Control** — `super_admin`, `admin`, `manager`, `sales_staff`
- 🛒 **Point of Sale (POS)** — Fast sales processing with barcode lookup
- 📦 **Purchase Management** — Supplier orders with approval workflow
- 🔄 **Inventory Adjustments** — Damage, transfer, and physical verification
- 📊 **Dashboard** — Real-time KPIs, low stock alerts, and sales summaries
- 🧾 **GST-Compliant Invoicing** — PDF invoices via TCPDF
- 📈 **Reports** — Sales, purchase, stock, and profit reports (PDF & Excel export)
- 👥 **Customer Management** — Credit limits, outstanding balances
- 🏭 **Supplier Management** — GST numbers, contact details
- 📋 **Audit Trail** — Every user action logged with IP address
- 📁 **Stock Ledger** — Full traceability for every stock movement
- 🔔 **Low Stock Alerts** — Minimum stock threshold notifications

---

## 🛠️ Tech Stack

| Layer        | Technology                                           |
|--------------|------------------------------------------------------|
| Language     | PHP 8.3                                              |
| Database     | PostgreSQL                                           |
| PDF Export   | TCPDF `^6.6`                                         |
| Excel Export | PhpSpreadsheet `^2.1`                                |
| Frontend     | Vanilla HTML, CSS, JavaScript                        |
| Server       | PHP Built-in Dev Server / Apache                     |
| Autoloader   | PSR-4 via Composer                                   |

---

## 📁 Project Structure

```
c:\amman\
├── app/
│   ├── Controllers/          # Request handlers
│   │   ├── AuthController.php
│   │   ├── DashboardController.php
│   │   ├── ProductController.php
│   │   ├── CategoryController.php
│   │   ├── SupplierController.php
│   │   ├── CustomerController.php
│   │   ├── PurchaseController.php
│   │   ├── SalesController.php
│   │   ├── InventoryController.php
│   │   ├── ReportController.php
│   │   └── SettingController.php
│   ├── Models/               # Database models
│   │   ├── User.php
│   │   ├── Product.php
│   │   ├── Category.php
│   │   ├── Supplier.php
│   │   ├── Customer.php
│   │   ├── Purchase.php
│   │   ├── Sale.php
│   │   ├── Inventory.php
│   │   ├── Setting.php
│   │   └── AuditTrail.php
│   ├── Views/                # PHP view templates
│   │   ├── layouts/          # Shared layout (header, sidebar, footer)
│   │   ├── auth/
│   │   ├── dashboard/
│   │   ├── products/
│   │   ├── categories/
│   │   ├── suppliers/
│   │   ├── customers/
│   │   ├── purchases/
│   │   ├── sales/
│   │   ├── inventory/
│   │   ├── reports/
│   │   ├── settings/
│   │   └── audit/
│   ├── Core/                 # Mini MVC framework
│   │   ├── Router.php
│   │   ├── Controller.php
│   │   ├── Model.php
│   │   ├── Database.php
│   │   ├── Session.php
│   │   ├── Request.php
│   │   └── Response.php
│   └── Helpers/
│       └── AuditLogger.php
├── database/
│   ├── schema.sql            # Full DB schema with triggers
│   ├── seed.sql              # Sample / seed data
│   └── disable_sale_trigger.sql
├── public/
│   ├── index.php             # Application entry point
│   ├── .htaccess             # URL rewriting
│   └── assets/
│       └── js/
│           └── main.js
├── php83/                    # Bundled PHP 8.3 binary (Windows)
├── vendor/                   # Composer dependencies
├── composer.json
└── README.md
```

---

## 🗄️ Database Schema

The system uses **PostgreSQL** with the following tables:

| Table                        | Description                                      |
|------------------------------|--------------------------------------------------|
| `settings`                   | App-wide configuration key-value store           |
| `users`                      | System users with roles                          |
| `categories`                 | Product categories                               |
| `products`                   | Product master with pricing, stock & GST info    |
| `suppliers`                  | Supplier details with GST numbers                |
| `customers`                  | Customer master with credit limits               |
| `purchases`                  | Purchase orders (pending → approved → returned)  |
| `purchase_items`             | Line items for each purchase                     |
| `sales`                      | Sales invoices with payment tracking             |
| `sale_items`                 | Line items for each sale                         |
| `inventory_adjustments`      | Stock adjustments (damage, transfer, etc.)       |
| `inventory_adjustment_items` | Items within each adjustment                     |
| `stock_ledger`               | Full stock movement history per product          |
| `audit_trails`               | All user actions with IP and timestamp           |

### Database Triggers

The schema uses **PostgreSQL triggers** for automated stock management:

| Trigger                       | Behaviour                                                            |
|-------------------------------|----------------------------------------------------------------------|
| `trg_purchase_status_change`  | Increases stock on approval; reverses stock on return                |
| `trg_sale_insert`             | Decreases stock on sale; updates customer outstanding balance        |
| `trg_adjustment_insert`       | Applies adjustments to stock and writes stock ledger entries         |

---

## 👤 Roles & Permissions

| Role          | Access Level                                             |
|---------------|----------------------------------------------------------|
| `super_admin` | Full system access, user management, settings            |
| `admin`       | All modules except user role management                  |
| `manager`     | Purchases, inventory, reports                            |
| `sales_staff` | POS sales only                                           |

---

## ✅ Prerequisites

- **PHP 8.3** (bundled at `php83/` for Windows, or install globally)
- **PostgreSQL** (running as a service)
- **Composer** (bundled as `composer.phar`)

---

## 🚀 Installation

### 1. Clone / Extract the project

Place the project at `C:\amman\` (or your preferred path).

### 2. Install PHP dependencies

```powershell
C:\amman\php83\php.exe composer.phar install
```

### 3. Set up the Database

Create a PostgreSQL database and run the schema:

```powershell
# Create the database
psql -U postgres -c "CREATE DATABASE hardware_shop;"

# Run the full schema (tables + triggers)
psql -U postgres -d hardware_shop -f C:\amman\database\schema.sql

# (Optional) Load sample seed data
psql -U postgres -d hardware_shop -f C:\amman\database\seed.sql
```

### 4. Configure Database Connection

Update the database credentials in `app/Core/Database.php`:

```php
$dsn      = "pgsql:host=localhost;port=5432;dbname=hardware_shop";
$user     = "postgres";
$password = "your_password";
```

---

## ▶️ Running the Application

### 1. Verify PostgreSQL is running

```powershell
Get-Service | Where-Object { $_.Name -like "*postgres*" }
```

### 2. Start the PHP built-in server

```powershell
C:\amman\php83\php.exe -S localhost:8000 -t C:\amman\public
```

### 3. Open in browser

```
http://localhost:8000
```

---

## 📦 Modules Overview

### 🏠 Dashboard
Real-time overview of today's sales, purchase totals, low-stock items, and recent transactions.

### 🔖 Categories
Manage product categories with active/inactive status control.

### 📦 Products
Full product master management including:
- Product code & barcode
- Brand, unit of measure, rack location
- Purchase price, selling price, GST percentage
- Minimum stock threshold for low-stock alerts
- Product image upload

### 🏭 Suppliers
Manage supplier records including GST number, contact person, mobile, email, and address.

### 👥 Customers
Manage customers with credit limits. Outstanding balances are updated automatically when a credit sale is made.

### 🛒 Purchases
- Create purchase orders from suppliers
- **Approve** a purchase → stock increases automatically via trigger
- **Mark as Returned** → stock decreases automatically via trigger

### 💰 Sales (POS)
- Fast invoice creation with barcode / product search
- Supports **Cash**, **Card**, **UPI**, and **Credit** payment methods
- Partial payment tracking updates customer outstanding balance
- PDF invoice generation with TCPDF

### 🔄 Inventory Adjustments
Record stock corrections with types:
- `adjustment` — general stock correction
- `transfer` — inter-location transfer
- `damaged` — write-off damaged items
- `physical_verification` — reconcile counted vs system stock

All adjustments are reflected in the stock ledger automatically.

### ⚙️ Settings
App-wide configuration (shop name, address, GST number, etc.).

### 📋 Audit Trail
Searchable log of all user actions with timestamps and IP addresses.

---

## 📊 Reports & Exports

Available from the **Reports** module:

| Report         | Export Formats |
|----------------|----------------|
| Sales Report   | PDF, Excel     |
| Purchase Report| PDF, Excel     |
| Stock Report   | PDF, Excel     |
| Profit Report  | PDF, Excel     |
| Stock Ledger   | PDF, Excel     |

---

## 🔍 Audit Trail

Every significant action (login, create, update, delete, approve) is automatically logged via the `AuditLogger` helper:

```php
use App\Helpers\AuditLogger;

AuditLogger::log('product_created', ['product_id' => 42, 'name' => 'Hammer']);
```

Each log entry records:
- `user_id` — who performed the action
- `action` — action identifier string
- `details` — JSON payload with context
- `ip_address` — client IP address
- `created_at` — timestamp

---

## 📄 License

This project is proprietary software intended for internal business use.
