-- Seed Data for Hardware Shop Inventory Management System

-- 1. Seed settings
INSERT INTO settings (key, value) VALUES
('shop_name', 'AMMAN TRADERS'),
('shop_logo', ''),
('shop_address', '201-Big Bazaar Street,Dindigul-624001'),
('shop_gst', '33AKEPR5347P1ZL'),
('shop_phone', '+91 12356'),
('shop_email', 'rajammandgl@gmail.com'),
('invoice_prefix', 'INV-'),
('currency', 'INR'),
('currency_symbol', '₹'),
('tax_settings', 'exclusive');

-- 2. Seed default users (Password for all: admin123)
-- Hash: $2y$10$ilS/rmK123.xj6ZVka7GYeb37UQmt1fehe9MmtceOOISboGvyh/oy
INSERT INTO users (username, password, role, status) VALUES
('superadmin', '$2y$10$ilS/rmK123.xj6ZVka7GYeb37UQmt1fehe9MmtceOOISboGvyh/oy', 'super_admin', 'active'),
('admin', '$2y$10$ilS/rmK123.xj6ZVka7GYeb37UQmt1fehe9MmtceOOISboGvyh/oy', 'admin', 'active'),
('manager', '$2y$10$ilS/rmK123.xj6ZVka7GYeb37UQmt1fehe9MmtceOOISboGvyh/oy', 'manager', 'active'),
('sales', '$2y$10$ilS/rmK123.xj6ZVka7GYeb37UQmt1fehe9MmtceOOISboGvyh/oy', 'sales_staff', 'active');

-- 3. Seed categories
INSERT INTO categories (name, description, status) VALUES
('Hand Tools', 'Manual tools including hammers, screwdrivers, wrenches, etc.', 'active'),
('Power Tools', 'Electric or battery operated drills, saws, grinders, etc.', 'active'),
('Fasteners', 'Nails, screws, bolts, nuts, and anchors.', 'active'),
('Plumbing', 'Pipes, fittings, valves, and plumbing adhesives.', 'active'),
('Electrical', 'Wires, switches, LED bulbs, tape, and conduits.', 'active'),
('Safety Equipment', 'Helmets, gloves, safety glasses, and high-vis vests.', 'active');

-- 4. Seed suppliers
INSERT INTO suppliers (supplier_code, supplier_name, contact_person, mobile, email, gst_number, address, status) VALUES
('SUP001', 'Apex Tool Group', 'John Doe', '9876543210', 'contact@apextools.com', '29AAAAA1111A1Z1', '12 Industry Ave, Bangalore', 'active'),
('SUP002', 'Bosch Power Supply', 'Jane Smith', '9876543211', 'sales@bosch.com', '27BBBBB2222B2Z2', '45 Powertool Road, Mumbai', 'active'),
('SUP003', 'Supreme Pipe Distributors', 'David Miller', '9876543212', 'logistics@supremepipes.com', '24DDDDD4444D4Z4', 'Sector 5, Chennai', 'active');

-- 5. Seed customers
INSERT INTO customers (customer_code, name, mobile, email, address, gst_number, credit_limit, outstanding_balance) VALUES
('CUST001', 'Walk-In Customer', '0000000000', 'walkin@shop.com', 'N/A', NULL, 0.00, 0.00),
('CUST002', 'Metro Builders & Contractors', '9876543222', 'accounts@metrobuilders.com', '88 Commercial Ring Road, Bangalore', '29CCCCC3333C3Z3', 50000.00, 0.00),
('CUST003', 'Rajesh Electricals', '9876543223', 'rajesh.elec@gmail.com', 'Shop 4, Market Complex, Bangalore', NULL, 15000.00, 2500.00);

-- 6. Seed products
-- STANLEY Claw Hammer 16oz (Hand Tools)
INSERT INTO products (product_code, barcode, product_name, category_id, brand, unit, purchase_price, selling_price, gst_percentage, current_stock, minimum_stock, rack_location, image_path, status)
VALUES ('PRD001', '8901234567890', 'Claw Hammer 16oz', 1, 'Stanley', 'pcs', 250.00, 399.00, 18.00, 50, 10, 'A-12', '', 'active');

-- BOSCH Rotary Hammer Drill (Power Tools)
INSERT INTO products (product_code, barcode, product_name, category_id, brand, unit, purchase_price, selling_price, gst_percentage, current_stock, minimum_stock, rack_location, image_path, status)
VALUES ('PRD002', '8901234567891', 'Rotary Hammer Drill', 2, 'Bosch', 'pcs', 3500.00, 4999.00, 18.00, 15, 3, 'B-05', '', 'active');

-- HILTI Drywall Screws Box (Fasteners)
INSERT INTO products (product_code, barcode, product_name, category_id, brand, unit, purchase_price, selling_price, gst_percentage, current_stock, minimum_stock, rack_location, image_path, status)
VALUES ('PRD003', '8901234567892', 'Drywall Screws Box (1000pcs)', 3, 'Hilti', 'box', 450.00, 650.00, 18.00, 80, 15, 'C-08', '', 'active');

-- SUPREME PVC Pipe 1/2 Inch (6m) (Plumbing)
INSERT INTO products (product_code, barcode, product_name, category_id, brand, unit, purchase_price, selling_price, gst_percentage, current_stock, minimum_stock, rack_location, image_path, status)
VALUES ('PRD004', '8901234567893', 'PVC Pipe 1/2 Inch (6m)', 4, 'Supreme', 'pcs', 120.00, 180.00, 18.00, 100, 20, 'D-01', '', 'active');

-- PHILIPS LED Bulb 9W (Electrical)
INSERT INTO products (product_code, barcode, product_name, category_id, brand, unit, purchase_price, selling_price, gst_percentage, current_stock, minimum_stock, rack_location, image_path, status)
VALUES ('PRD005', '8901234567894', 'LED Bulb 9W', 5, 'Philips', 'pcs', 60.00, 100.00, 18.00, 200, 30, 'E-03', '', 'active');

-- 3M Safety Helmet Yellow (Safety Equipment) - triggers low stock alert! (stock 5 < min 10)
INSERT INTO products (product_code, barcode, product_name, category_id, brand, unit, purchase_price, selling_price, gst_percentage, current_stock, minimum_stock, rack_location, image_path, status)
VALUES ('PRD006', '8901234567895', 'Safety Helmet Yellow', 6, '3M', 'pcs', 180.00, 299.00, 18.00, 5, 10, 'F-02', '', 'active');
