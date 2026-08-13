-- PostgreSQL schema for Real Estate Listing System

-- Users table for all users (buyers, sellers, admins)
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role VARCHAR(20) NOT NULL CHECK (role IN ('buyer', 'seller', 'admin')) DEFAULT 'buyer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Properties table for real estate listings
CREATE TABLE IF NOT EXISTS properties (
    id SERIAL PRIMARY KEY,
    seller_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    price DECIMAL(12, 2) NOT NULL,
    bedrooms INTEGER NOT NULL,
    bathrooms INTEGER NOT NULL,
    area DECIMAL(10, 2) NOT NULL,
    address VARCHAR(255) NOT NULL,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL,
    zip_code VARCHAR(20) NOT NULL,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    property_type VARCHAR(20) NOT NULL CHECK (property_type IN ('house', 'apartment', 'condo', 'land', 'commercial')),
    status VARCHAR(20) NOT NULL CHECK (status IN ('for_sale', 'for_rent', 'sold', 'rented')),
    featured BOOLEAN DEFAULT FALSE,
    image1 VARCHAR(255) NOT NULL,
    image2 VARCHAR(255),
    image3 VARCHAR(255),
    image4 VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Inquiries table for property inquiries
CREATE TABLE IF NOT EXISTS inquiries (
    id SERIAL PRIMARY KEY,
    property_id INTEGER NOT NULL REFERENCES properties(id) ON DELETE CASCADE,
    buyer_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    seller_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    message TEXT NOT NULL,
    status VARCHAR(20) CHECK (status IN ('new', 'read', 'replied', 'closed')) DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Favorites table for saved properties
CREATE TABLE IF NOT EXISTS favorites (
    id SERIAL PRIMARY KEY,
    buyer_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    property_id INTEGER NOT NULL REFERENCES properties(id) ON DELETE CASCADE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (buyer_id, property_id)
);

-- Reports table for reported listings
CREATE TABLE IF NOT EXISTS reports (
    id SERIAL PRIMARY KEY,
    property_id INTEGER NOT NULL REFERENCES properties(id) ON DELETE CASCADE,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    reason TEXT NOT NULL,
    status VARCHAR(20) CHECK (status IN ('pending', 'resolved', 'dismissed')) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create functions for updating timestamps
CREATE OR REPLACE FUNCTION update_modified_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Create triggers for updating timestamps
CREATE TRIGGER update_user_modtime
BEFORE UPDATE ON users
FOR EACH ROW EXECUTE FUNCTION update_modified_column();

CREATE TRIGGER update_property_modtime
BEFORE UPDATE ON properties
FOR EACH ROW EXECUTE FUNCTION update_modified_column();

CREATE TRIGGER update_inquiry_modtime
BEFORE UPDATE ON inquiries
FOR EACH ROW EXECUTE FUNCTION update_modified_column();

CREATE TRIGGER update_report_modtime
BEFORE UPDATE ON reports
FOR EACH ROW EXECUTE FUNCTION update_modified_column();

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
(2, 'Luxury Villa with Pool', 'Beautiful luxury villa with swimming pool and garden. Perfect for families.', 750000.00, 4, 3, 2500.00, '123 Luxury Lane', 'Beverly Hills', 'CA', '90210', 34.0736, -118.4004, 'house', 'for_sale', TRUE, 'https://pixabay.com/get/g48813c3c4b1c54c75a6c1ad75c62fd6b13d9ca382dbcf348f84e9cb7bd32aadde9e5a1f0dd12552a10416caa682fbb0a8cfb21902acc9eea0418219388b3b237_1280.jpg', 'https://pixabay.com/get/g165d0bb0b534699d16749c17f6627377fa56f348f084d051fa920b25abcd6660a8797115b30b506a1d0629aa2c97b7113392eda0f4f7b5323680253a08a234be_1280.jpg', 'https://pixabay.com/get/g3c3c413e9b95dc39470699f68b05ab10ed690956856ee101ad02cc2e0da8f0112fdd95d3335004519cc15eb1c9930f6641b75f529b0b70f0520b2f961163c648_1280.jpg'),

(2, 'Modern Apartment Downtown', 'Stylish modern apartment in the heart of downtown. Close to restaurants and shops.', 350000.00, 2, 2, 1200.00, '456 Urban Street', 'Los Angeles', 'CA', '90012', 34.0522, -118.2437, 'apartment', 'for_sale', TRUE, 'https://pixabay.com/get/g83bbcb7fb7ac15155a04acf636462491931b6cb04a226b82ecec45c87f242e181b11b6c7d6dc3a86e2696be4894ba08f3863cb64bfa22a365027abd568052108_1280.jpg', 'https://pixabay.com/get/gb2ccc04465300f479f87035fe895cc983fd6e4022b11fa3eb8fd53495116fcda1b00f56b739bbb633feeb96de766c5a9d6b5c802f1f8bf77a99c9c2e8d37d83c_1280.jpg', 'https://pixabay.com/get/g165d0bb0b534699d16749c17f6627377fa56f348f084d051fa920b25abcd6660a8797115b30b506a1d0629aa2c97b7113392eda0f4f7b5323680253a08a234be_1280.jpg'),

(3, 'Family Home with Garden', 'Spacious family home with large garden and modern kitchen.', 450000.00, 3, 2, 1800.00, '789 Family Road', 'Pasadena', 'CA', '91101', 34.1478, -118.1445, 'house', 'for_sale', FALSE, 'https://pixabay.com/get/g6d4c467a3666eab2d21f8a557a5571dd903263035aa1f54c0d2e2868e8ea8a5bc245fcec5d07ca7adf2e745de80f64a833da466bcee0db5043229f8f1d255522_1280.jpg', 'https://pixabay.com/get/gcd67267c234f76f7b705a47672907145ec492181668fb2060eed17a208e5eb1443fb1429e583e2ecd4f6d8d36b5640755cbfcf7f6877c78fa4e02ad05328502a_1280.jpg', 'https://pixabay.com/get/g6aee0f5e3fcf475706cbc804f7e7aa569cab2e16d59dda71ac640e01daf66028305299b889e81b4ad82a1fc91bd92ab308fb0364138e09855249f06b5b2580d2_1280.jpg'),

(3, 'Beachfront Condo', 'Stunning beachfront condo with amazing ocean views. Newly renovated.', 580000.00, 2, 2, 1400.00, '101 Beach Drive', 'Malibu', 'CA', '90265', 34.0259, -118.7798, 'condo', 'for_sale', TRUE, 'https://pixabay.com/get/gb74c7be7cefc0d8f3ad165d869a52f0ca7db30a3b2c55d86b17cddc6059c539fba3f6132662f70439b331e364d9a678d256df4f72334e8057ec1918ab0ba370f_1280.jpg', 'https://pixabay.com/get/g3c3c413e9b95dc39470699f68b05ab10ed690956856ee101ad02cc2e0da8f0112fdd95d3335004519cc15eb1c9930f6641b75f529b0b70f0520b2f961163c648_1280.jpg', 'https://pixabay.com/get/g83bbcb7fb7ac15155a04acf636462491931b6cb04a226b82ecec45c87f242e181b11b6c7d6dc3a86e2696be4894ba08f3863cb64bfa22a365027abd568052108_1280.jpg'),

(2, 'Luxury Penthouse', 'Exclusive penthouse with panoramic city views. High-end finishes throughout.', 1200000.00, 3, 3, 2800.00, '222 Sky Tower', 'Los Angeles', 'CA', '90024', 34.0635, -118.4455, 'apartment', 'for_sale', TRUE, 'https://pixabay.com/get/g5ab49a8aeb2fb82f243f4ae574ef29020f30156a0124c6ef05d3289cc470aaed05d56abb6f8f0dba3b6251ca481dcd67247dfed09bb2c4554145d32829543817_1280.jpg', 'https://pixabay.com/get/g165d0bb0b534699d16749c17f6627377fa56f348f084d051fa920b25abcd6660a8797115b30b506a1d0629aa2c97b7113392eda0f4f7b5323680253a08a234be_1280.jpg', 'https://pixabay.com/get/g3c3c413e9b95dc39470699f68b05ab10ed690956856ee101ad02cc2e0da8f0112fdd95d3335004519cc15eb1c9930f6641b75f529b0b70f0520b2f961163c648_1280.jpg'),

(3, 'Modern Architectural Home', 'Stunning architectural masterpiece with open floor plan and designer features.', 875000.00, 4, 3, 3200.00, '333 Modern Way', 'Hollywood Hills', 'CA', '90046', 34.1153, -118.3694, 'house', 'for_sale', FALSE, 'https://pixabay.com/get/gdc16a5768d9e2499140c980ded7f62b863b359136e288fb251c83f731e90c32297bbde37f4684e62ef212737a211e4a4664164ca24f4d634cd1c9ed41484858d_1280.jpg', 'https://pixabay.com/get/gaf0feda13ef4a6b1e447b1e956b4d28545753fa18c9d78ed4ab231c76e52c1a7baee48670bf7af99764f568aee125f248ac428709c4605dd420b8d3c6f395493_1280.jpg', 'https://pixabay.com/get/gb069e966ac2c973b1f89d83851ee3cf35722e36f93303c01246e9399edb882aeb2ad151c7ebd16799023270cd40b5cc94e663e090e918f72ee65d61e693f8e16_1280.jpg');

-- Insert sample inquiries
INSERT INTO inquiries (property_id, buyer_id, seller_id, message, status) VALUES
(1, 4, 2, 'I am interested in viewing this property. When would be a good time?', 'new'),
(3, 4, 3, 'Does this property have a garage? How old is the roof?', 'read'),
(2, 5, 2, 'Is the price negotiable? I love the property but it is slightly above my budget.', 'replied');

-- Insert sample favorites
INSERT INTO favorites (buyer_id, property_id) VALUES
(4, 1),
(4, 3),
(5, 2),
(5, 4);