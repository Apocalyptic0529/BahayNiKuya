
-- Create the database
CREATE DATABASE IF NOT EXISTS real_estate;
USE real_estate;

-- Users table for all users (buyers, sellers, admins)
CREATE TABLE IF NOT EXISTS users (
    id INT(11) NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role ENUM('buyer', 'seller', 'admin') NOT NULL DEFAULT 'buyer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);

-- Properties table for real estate listings
CREATE TABLE IF NOT EXISTS properties (
    id INT(11) NOT NULL AUTO_INCREMENT,
    seller_id INT(11) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    price DECIMAL(12, 2) NOT NULL,
    bedrooms INT(2) NOT NULL,
    bathrooms INT(2) NOT NULL,
    area DECIMAL(10, 2) NOT NULL,
    address VARCHAR(255) NOT NULL,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL,
    zip_code VARCHAR(20) NOT NULL,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    property_type ENUM('house', 'apartment', 'condo', 'land', 'commercial') NOT NULL,
    status ENUM('pending', 'for_sale', 'for_rent', 'sold', 'rented') NOT NULL,
    featured BOOLEAN DEFAULT FALSE,
    image1 VARCHAR(255) NOT NULL,
    image2 VARCHAR(255),
    image3 VARCHAR(255),
    image4 VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Inquiries table for property inquiries
CREATE TABLE IF NOT EXISTS inquiries (
    id INT(11) NOT NULL AUTO_INCREMENT,
    property_id INT(11) NOT NULL,
    buyer_id INT(11) NOT NULL,
    seller_id INT(11) NOT NULL,
    message TEXT NOT NULL,
    reply_message TEXT,
    reply_date TIMESTAMP NULL,
    status ENUM('new', 'read', 'replied', 'closed') DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
    FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Seller Applications table
CREATE TABLE IF NOT EXISTS seller_applications (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    business_name VARCHAR(255),
    experience TEXT NOT NULL,
    license_number VARCHAR(100),
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Favorites table for saved properties
CREATE TABLE IF NOT EXISTS favorites (
    id INT(11) NOT NULL AUTO_INCREMENT,
    buyer_id INT(11) NOT NULL,
    property_id INT(11) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_favorite (buyer_id, property_id),
    FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
);

-- Reports table for reported listings
CREATE TABLE IF NOT EXISTS reports (
    id INT(11) NOT NULL AUTO_INCREMENT,
    property_id INT(11) NOT NULL,
    user_id INT(11) NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('pending', 'resolved', 'dismissed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert sample data - Admin user (admin password is "admin123")
INSERT INTO users (name, email, password, role) VALUES 
('Admin User', 'admin@realestate.com', '$2y$10$NIjX2ELt4ODSW2rt1.7Xo.Q4BQ8MKNW0LWhNPA7QKVXerHzwb6kDG', 'admin');

-- Insert sample data - Sellers
INSERT INTO users (name, email, password, phone, role) VALUES 
('John Seller', 'john@example.com', '$2y$10$aBcDeFgHiJkLmNoPqRsTu.vwxYz12345678ABCDEFGHIJKLMN', '555-123-4567', 'seller'),
('Sarah Agent', 'sarah@example.com', '$2y$10$aBcDeFgHiJkLmNoPqRsTu.vwxYz12345678ABCDEFGHIJKLMN', '555-987-6543', 'seller');

-- Insert sample data - Buyers
INSERT INTO users (name, email, password, phone, role) VALUES 
('Mike Buyer', 'mike@example.com', '$2y$10$aBcDeFgHiJkLmNoPqRsTu.vwxYz12345678ABCDEFGHIJKLMN', '555-555-5555', 'buyer'),
('Lisa House', 'lisa@example.com', '$2y$10$aBcDeFgHiJkLmNoPqRsTu.vwxYz12345678ABCDEFGHIJKLMN', '555-444-3333', 'buyer');

-- Insert sample properties
INSERT INTO properties (seller_id, title, description, price, bedrooms, bathrooms, area, address, city, state, zip_code, latitude, longitude, property_type, status, featured, image1, image2, image3) VALUES
(2, 'Luxury Villa with Pool', 'Beautiful luxury villa with swimming pool and garden. Perfect for families.', 750000.00, 4, 3, 2500.00, '123 Luxury Lane', 'Beverly Hills', 'CA', '90210', 34.0736, -118.4004, 'house', 'for_sale', 1, 'https://pixabay.com/get/g48813c3c4b1c54c75a6c1ad75c62fd6b13d9ca382dbcf348f84e9cb7bd32aadde9e5a1f0dd12552a10416caa682fbb0a8cfb21902acc9eea0418219388b3b237_1280.jpg', 'https://pixabay.com/get/g165d0bb0b534699d16749c17f6627377fa56f348f084d051fa920b25abcd6660a8797115b30b506a1d0629aa2c97b7113392eda0f4f7b5323680253a08a234be_1280.jpg', 'https://pixabay.com/get/g3c3c413e9b95dc39470699f68b05ab10ed690956856ee101ad02cc2e0da8f0112fdd95d3335004519cc15eb1c9930f6641b75f529b0b70f0520b2f961163c648_1280.jpg'),
(2, 'Modern Apartment Downtown', 'Stylish modern apartment in the heart of downtown. Close to restaurants and shops.', 350000.00, 2, 2, 1200.00, '456 Urban Street', 'Los Angeles', 'CA', '90012', 34.0522, -118.2437, 'apartment', 'for_sale', 1, 'https://pixabay.com/get/g83bbcb7fb7ac15155a04acf636462491931b6cb04a226b82ecec45c87f242e181b11b6c7d6dc3a86e2696be4894ba08f3863cb64bfa22a365027abd568052108_1280.jpg', 'https://pixabay.com/get/gb2ccc04465300f479f87035fe895cc983fd6e4022b11fa3eb8fd53495116fcda1b00f56b739bbb633feeb96de766c5a9d6b5c802f1f8bf77a99c9c2e8d37d83c_1280.jpg', 'https://pixabay.com/get/g165d0bb0b534699d16749c17f6627377fa56f348f084d051fa920b25abcd6660a8797115b30b506a1d0629aa2c97b7113392eda0f4f7b5323680253a08a234be_1280.jpg'),
(3, 'Family Home with Garden', 'Spacious family home with large garden and modern kitchen.', 450000.00, 3, 2, 1800.00, '789 Family Road', 'Pasadena', 'CA', '91101', 34.1478, -118.1445, 'house', 'for_sale', 0, 'https://pixabay.com/get/g6d4c467a3666eab2d21f8a557a5571dd903263035aa1f54c0d2e2868e8ea8a5bc245fcec5d07ca7adf2e745de80f64a833da466bcee0db5043229f8f1d255522_1280.jpg', 'https://pixabay.com/get/gcd67267c234f76f7b705a47672907145ec492181668fb2060eed17a208e5eb1443fb1429e583e2ecd4f6d8d36b5640755cbfcf7f6877c78fa4e02ad05328502a_1280.jpg', 'https://pixabay.com/get/g6aee0f5e3fcf475706cbc804f7e7aa569cab2e16d59dda71ac640e01daf66028305299b889e81b4ad82a1fc91bd92ab308fb0364138e09855249f06b5b2580d2_1280.jpg');

-- Insert sample inquiries
INSERT INTO inquiries (property_id, buyer_id, seller_id, message, status) VALUES
(1, 4, 2, 'I am interested in viewing this property. When would be a good time?', 'new'),
(2, 4, 2, 'Does this property have a garage? How old is the roof?', 'read'),
(3, 5, 3, 'Is the price negotiable? I love the property but it is slightly above my budget.', 'replied');

-- Insert sample favorites
INSERT INTO favorites (buyer_id, property_id) VALUES
(4, 1),
(4, 3),
(5, 2);
