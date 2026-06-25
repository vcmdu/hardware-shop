-- PostgreSQL Database Schema for Hardware Shop Inventory Management System

-- Drop tables if they exist to allow clean reinstall
DROP TABLE IF EXISTS audit_trails CASCADE;
DROP TABLE IF EXISTS stock_ledger CASCADE;
DROP TABLE IF EXISTS inventory_adjustment_items CASCADE;
DROP TABLE IF EXISTS inventory_adjustments CASCADE;
DROP TABLE IF EXISTS sale_items CASCADE;
DROP TABLE IF EXISTS sales CASCADE;
DROP TABLE IF EXISTS purchase_items CASCADE;
DROP TABLE IF EXISTS purchases CASCADE;
DROP TABLE IF EXISTS customers CASCADE;
DROP TABLE IF EXISTS suppliers CASCADE;
DROP TABLE IF EXISTS products CASCADE;
DROP TABLE IF EXISTS categories CASCADE;
DROP TABLE IF EXISTS users CASCADE;
DROP TABLE IF EXISTS settings CASCADE;

-- 1. Settings Table
CREATE TABLE settings (
    key VARCHAR(100) PRIMARY KEY,
    value TEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Users Table
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL CHECK (role IN ('super_admin', 'admin', 'manager', 'sales_staff')),
    status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active', 'inactive')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. Categories Table
CREATE TABLE categories (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active', 'inactive')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 4. Products Table
CREATE TABLE products (
    id SERIAL PRIMARY KEY,
    product_code VARCHAR(50) UNIQUE NOT NULL,
    barcode VARCHAR(100) UNIQUE NOT NULL,
    product_name VARCHAR(150) NOT NULL,
    category_id INT REFERENCES categories(id) ON DELETE RESTRICT,
    brand VARCHAR(100),
    unit VARCHAR(20) DEFAULT 'pcs',
    purchase_price NUMERIC(12, 2) NOT NULL DEFAULT 0.00,
    selling_price NUMERIC(12, 2) NOT NULL DEFAULT 0.00,
    gst_percentage NUMERIC(5, 2) NOT NULL DEFAULT 18.00,
    current_stock INT NOT NULL DEFAULT 0,
    minimum_stock INT NOT NULL DEFAULT 5,
    rack_location VARCHAR(50),
    image_path VARCHAR(255),
    status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active', 'inactive')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 5. Suppliers Table
CREATE TABLE suppliers (
    id SERIAL PRIMARY KEY,
    supplier_code VARCHAR(50) UNIQUE NOT NULL,
    supplier_name VARCHAR(150) NOT NULL,
    contact_person VARCHAR(100),
    mobile VARCHAR(20),
    email VARCHAR(100),
    gst_number VARCHAR(20),
    address TEXT,
    status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active', 'inactive')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 6. Customers Table
CREATE TABLE customers (
    id SERIAL PRIMARY KEY,
    customer_code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(150) NOT NULL,
    mobile VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    gst_number VARCHAR(20),
    credit_limit NUMERIC(12, 2) DEFAULT 0.00,
    outstanding_balance NUMERIC(12, 2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 7. Purchases Table
CREATE TABLE purchases (
    id SERIAL PRIMARY KEY,
    purchase_number VARCHAR(50) UNIQUE NOT NULL,
    supplier_id INT REFERENCES suppliers(id) ON DELETE RESTRICT,
    date DATE NOT NULL,
    discount NUMERIC(12, 2) DEFAULT 0.00,
    gst_total NUMERIC(12, 2) DEFAULT 0.00,
    grand_total NUMERIC(12, 2) DEFAULT 0.00,
    status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'approved', 'returned')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 8. Purchase Items Table
CREATE TABLE purchase_items (
    id SERIAL PRIMARY KEY,
    purchase_id INT REFERENCES purchases(id) ON DELETE CASCADE,
    product_id INT REFERENCES products(id) ON DELETE RESTRICT,
    quantity INT NOT NULL CHECK (quantity > 0),
    unit_cost NUMERIC(12, 2) NOT NULL,
    gst_percentage NUMERIC(5, 2) NOT NULL,
    discount NUMERIC(12, 2) DEFAULT 0.00,
    total NUMERIC(12, 2) NOT NULL
);

-- 9. Sales (Invoices) Table
CREATE TABLE sales (
    id SERIAL PRIMARY KEY,
    invoice_number VARCHAR(50) UNIQUE NOT NULL,
    customer_id INT REFERENCES customers(id) ON DELETE RESTRICT,
    date DATE NOT NULL,
    discount NUMERIC(12, 2) DEFAULT 0.00,
    gst_total NUMERIC(12, 2) DEFAULT 0.00,
    grand_total NUMERIC(12, 2) DEFAULT 0.00,
    payment_method VARCHAR(20) NOT NULL DEFAULT 'cash' CHECK (payment_method IN ('cash', 'card', 'upi', 'credit')),
    payment_status VARCHAR(20) DEFAULT 'paid' CHECK (payment_status IN ('paid', 'unpaid', 'partial')),
    paid_amount NUMERIC(12, 2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 10. Sale Items Table
CREATE TABLE sale_items (
    id SERIAL PRIMARY KEY,
    sale_id INT REFERENCES sales(id) ON DELETE CASCADE,
    product_id INT REFERENCES products(id) ON DELETE RESTRICT,
    quantity INT NOT NULL CHECK (quantity > 0),
    price NUMERIC(12, 2) NOT NULL,
    gst_percentage NUMERIC(5, 2) NOT NULL,
    discount NUMERIC(12, 2) DEFAULT 0.00,
    total NUMERIC(12, 2) NOT NULL
);

-- 11. Inventory Adjustments Table
CREATE TABLE inventory_adjustments (
    id SERIAL PRIMARY KEY,
    reference_number VARCHAR(50) UNIQUE NOT NULL,
    type VARCHAR(50) NOT NULL CHECK (type IN ('adjustment', 'transfer', 'damaged', 'physical_verification')),
    description TEXT,
    date DATE NOT NULL,
    created_by INT REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 12. Inventory Adjustment Items Table
CREATE TABLE inventory_adjustment_items (
    id SERIAL PRIMARY KEY,
    adjustment_id INT REFERENCES inventory_adjustments(id) ON DELETE CASCADE,
    product_id INT REFERENCES products(id) ON DELETE RESTRICT,
    quantity_before INT NOT NULL,
    quantity_after INT NOT NULL,
    quantity_adjusted INT NOT NULL, -- positive for add, negative for subtract
    reason VARCHAR(255)
);

-- 13. Stock Ledger Table
CREATE TABLE stock_ledger (
    id SERIAL PRIMARY KEY,
    product_id INT REFERENCES products(id) ON DELETE CASCADE,
    transaction_type VARCHAR(30) NOT NULL CHECK (transaction_type IN ('purchase', 'purchase_return', 'sale', 'sale_return', 'adjustment_add', 'adjustment_sub', 'transfer_out', 'transfer_in', 'damaged', 'physical_verification')),
    reference_id INT NOT NULL, -- matches purchases.id, sales.id, or inventory_adjustments.id
    quantity INT NOT NULL,
    balance_after INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 14. Audit Trails Table
CREATE TABLE audit_trails (
    id SERIAL PRIMARY KEY,
    user_id INT REFERENCES users(id) ON DELETE SET NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Database Indices for optimal lookup
CREATE INDEX idx_products_code ON products(product_code);
CREATE INDEX idx_products_barcode ON products(barcode);
CREATE INDEX idx_purchases_num ON purchases(purchase_number);
CREATE INDEX idx_sales_num ON sales(invoice_number);
CREATE INDEX idx_stock_ledger_prod ON stock_ledger(product_id);
CREATE INDEX idx_audit_user ON audit_trails(user_id);

-- TRIGGERS & FUNCTIONS

-- A. Trigger function for Purchase Approval / Return
CREATE OR REPLACE FUNCTION trg_process_purchase_status()
RETURNS TRIGGER AS $$
DECLARE
    item RECORD;
    current_stock_val INT;
BEGIN
    -- Only trigger if status has changed
    IF (TG_OP = 'UPDATE' AND OLD.status <> NEW.status) THEN
        -- Case 1: Status moves from 'pending' to 'approved'
        IF (OLD.status = 'pending' AND NEW.status = 'approved') THEN
            FOR item IN SELECT * FROM purchase_items WHERE purchase_id = NEW.id LOOP
                -- Lock product and update stock
                UPDATE products 
                SET current_stock = current_stock + item.quantity,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = item.product_id
                RETURNING current_stock INTO current_stock_val;
                
                -- Record in stock ledger
                INSERT INTO stock_ledger (product_id, transaction_type, reference_id, quantity, balance_after)
                VALUES (item.product_id, 'purchase', NEW.id, item.quantity, current_stock_val);
            END LOOP;
        
        -- Case 2: Status moves from 'approved' to 'returned'
        ELSIF (OLD.status = 'approved' AND NEW.status = 'returned') THEN
            FOR item IN SELECT * FROM purchase_items WHERE purchase_id = NEW.id LOOP
                -- Lock product and update stock (decrease stock)
                UPDATE products 
                SET current_stock = current_stock - item.quantity,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = item.product_id
                RETURNING current_stock INTO current_stock_val;
                
                -- Record in stock ledger
                INSERT INTO stock_ledger (product_id, transaction_type, reference_id, quantity, balance_after)
                VALUES (item.product_id, 'purchase_return', NEW.id, -item.quantity, current_stock_val);
            END LOOP;
        END IF;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_purchase_status_change
AFTER UPDATE ON purchases
FOR EACH ROW
EXECUTE FUNCTION trg_process_purchase_status();


-- B. Trigger function for Sales (POS) Insertion
CREATE OR REPLACE FUNCTION trg_process_sale_insertion()
RETURNS TRIGGER AS $$
DECLARE
    item RECORD;
    current_stock_val INT;
    unpaid_amount NUMERIC(12, 2);
BEGIN
    -- Decrease stock for each item sold
    FOR item IN SELECT * FROM sale_items WHERE sale_id = NEW.id LOOP
        UPDATE products 
        SET current_stock = current_stock - item.quantity,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = item.product_id
        RETURNING current_stock INTO current_stock_val;
        
        -- Record in stock ledger
        INSERT INTO stock_ledger (product_id, transaction_type, reference_id, quantity, balance_after)
        VALUES (item.product_id, 'sale', NEW.id, -item.quantity, current_stock_val);
    END LOOP;
    
    -- Handle customer credit/outstanding balance
    unpaid_amount := NEW.grand_total - NEW.paid_amount;
    IF (unpaid_amount > 0) THEN
        UPDATE customers
        SET outstanding_balance = outstanding_balance + unpaid_amount,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = NEW.customer_id;
    END IF;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_sale_insert
AFTER INSERT ON sales
FOR EACH ROW
EXECUTE FUNCTION trg_process_sale_insertion();


-- C. Trigger function for Inventory Adjustments
CREATE OR REPLACE FUNCTION trg_process_inventory_adjustment()
RETURNS TRIGGER AS $$
DECLARE
    item RECORD;
    current_stock_val INT;
    t_type VARCHAR(30);
BEGIN
    -- Determine transaction ledger type
    IF NEW.type = 'damaged' THEN
        t_type := 'damaged';
    ELSIF NEW.type = 'physical_verification' THEN
        t_type := 'physical_verification';
    ELSE
        t_type := 'adjustment_add'; -- fallback
    END IF;

    FOR item IN SELECT * FROM inventory_adjustment_items WHERE adjustment_id = NEW.id LOOP
        -- Adjust current stock in products table
        UPDATE products 
        SET current_stock = current_stock + item.quantity_adjusted,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = item.product_id
        RETURNING current_stock INTO current_stock_val;
        
        -- Overwrite t_type based on whether adjustment is positive or negative
        IF (item.quantity_adjusted < 0 AND t_type = 'adjustment_add') THEN
            t_type := 'adjustment_sub';
        END IF;
        
        -- Record in stock ledger
        INSERT INTO stock_ledger (product_id, transaction_type, reference_id, quantity, balance_after)
        VALUES (item.product_id, t_type, NEW.id, item.quantity_adjusted, current_stock_val);
    END LOOP;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_adjustment_insert
AFTER INSERT ON inventory_adjustments
FOR EACH ROW
EXECUTE FUNCTION trg_process_inventory_adjustment();
