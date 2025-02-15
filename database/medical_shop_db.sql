-- Create the database if it doesn't exist and use it
CREATE DATABASE IF NOT EXISTS msms_db2;
USE msms_db2;

-- Create medicines table
CREATE TABLE IF NOT EXISTS medicines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    `use` VARCHAR(255),
    selling_price DECIMAL(10,2) NOT NULL,
    available_quantity INT NOT NULL DEFAULT 0,
    expiry_date DATE DEFAULT NULL  -- Added expiry_date column here
);

-- Create purchases table
CREATE TABLE IF NOT EXISTS purchases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    medicine_id INT,
    quantity INT NOT NULL,
    purchase_price DECIMAL(10,2) NOT NULL,
    purchase_date DATE NOT NULL,
    expiry_date DATE NOT NULL,  -- Kept expiry_date in purchases table
    total_cost DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (medicine_id) REFERENCES medicines(id)
);

-- Create sales table
CREATE TABLE IF NOT EXISTS sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    medicine_id INT,
    quantity_sold INT NOT NULL,
    sale_price DECIMAL(10,2) NOT NULL,
    sale_date DATE NOT NULL,
    FOREIGN KEY (medicine_id) REFERENCES medicines(id)
);

-- Create bills table
CREATE TABLE IF NOT EXISTS bills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    total_amount DECIMAL(10,2) NOT NULL,
    bill_date DATE NOT NULL
);

-- Create bill_sales junction table
CREATE TABLE IF NOT EXISTS bill_sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bill_id INT,
    sale_id INT,
    FOREIGN KEY (bill_id) REFERENCES bills(id),
    FOREIGN KEY (sale_id) REFERENCES sales(id)
);

-- Create notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    medicine_id INT,
    message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('unread', 'read') DEFAULT 'unread',
    notified_until DATE,
    FOREIGN KEY (medicine_id) REFERENCES medicines(id),
    UNIQUE (medicine_id, notified_until)
);



ALTER TABLE sales
    ADD CONSTRAINT fk_medicine_id
    FOREIGN KEY (medicine_id)
    REFERENCES medicines(id)
    ON DELETE CASCADE;


ALTER TABLE bills
ADD COLUMN customer_name VARCHAR(255) NOT NULL;



-- Create admin_users table
CREATE TABLE admin_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin user (password: admin123)
INSERT INTO admin_users (username, password) 
VALUES ('Admin', 'Admin123');