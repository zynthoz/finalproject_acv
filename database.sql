-- Create the database
CREATE DATABASE IF NOT EXISTS db_serke_resortsystem_acv;
USE db_serke_resortsystem_acv;

-- Users table
CREATE TABLE IF NOT EXISTS tbl_users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin','employee','customer') NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    age INT,
    contact_num VARCHAR(20),
    gender VARCHAR(20),
    imgpath VARCHAR(255),
    otp VARCHAR(10),
    status VARCHAR(20) DEFAULT 'Pending'
);

-- Logs table
CREATE TABLE IF NOT EXISTS tbl_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action TEXT NOT NULL,
    datetime DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES tbl_users(user_id)
);

-- Accommodations table
CREATE TABLE IF NOT EXISTS tbl_accommodations (
    accommodation_id INT AUTO_INCREMENT PRIMARY KEY,
    accommodation_name VARCHAR(100) NOT NULL,
    description TEXT,
    capacity INT NOT NULL,
    price_per_night DECIMAL(10,2) NOT NULL,
    availability_status ENUM('available','unavailable') NOT NULL DEFAULT 'available',
    image_url VARCHAR(255)
);

-- Amenities table
CREATE TABLE IF NOT EXISTS tbl_amenities (
    amenity_id INT AUTO_INCREMENT PRIMARY KEY,
    amenity_name VARCHAR(100) NOT NULL,
    description TEXT,
    price_per_use DECIMAL(10,2) NOT NULL
);

-- Reservations table
CREATE TABLE IF NOT EXISTS tbl_reservations (
    reservation_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    accommodation_id INT NOT NULL,
    check_in_date DATE NOT NULL,
    check_out_date DATE NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    reservation_status ENUM('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
    FOREIGN KEY (user_id) REFERENCES tbl_users(user_id),
    FOREIGN KEY (accommodation_id) REFERENCES tbl_accommodations(accommodation_id)
);

-- Packages table
CREATE TABLE IF NOT EXISTS tbl_packages (
    package_id INT AUTO_INCREMENT PRIMARY KEY,
    package_name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    inclusion_details TEXT
);

-- Package accommodation mapping (one accommodation per package)
CREATE TABLE IF NOT EXISTS tbl_package_accommodations (
    package_id INT NOT NULL,
    accommodation_id INT NOT NULL,
    PRIMARY KEY (package_id),
    FOREIGN KEY (package_id) REFERENCES tbl_packages(package_id) ON DELETE CASCADE,
    FOREIGN KEY (accommodation_id) REFERENCES tbl_accommodations(accommodation_id) ON DELETE CASCADE
);

-- Package amenities mapping (many amenities per package)
CREATE TABLE IF NOT EXISTS tbl_package_amenities (
    package_id INT NOT NULL,
    amenity_id INT NOT NULL,
    PRIMARY KEY (package_id, amenity_id),
    FOREIGN KEY (package_id) REFERENCES tbl_packages(package_id) ON DELETE CASCADE,
    FOREIGN KEY (amenity_id) REFERENCES tbl_amenities(amenity_id) ON DELETE CASCADE
);

-- =====================
-- DUMMY DATA
-- =====================

-- Users (password is MD5 of the word after the username number)
-- admin1 / admin
-- employee1 / employee
-- customer1 / customer
-- customer2 / customer
INSERT INTO tbl_users (full_name, role, username, password, email, status) VALUES
('System Administrator', 'admin', 'admin1', MD5('admin'), 'admin@resort.com', 'Active'),
('Maria Santos', 'employee', 'employee1', MD5('employee'), 'maria@resort.com', 'Active'),
('Juan Dela Cruz', 'customer', 'customer1', MD5('customer'), 'juan@email.com', 'Active'),
('Ana Reyes', 'customer', 'customer2', MD5('customer'), 'ana@email.com', 'Active');

-- Accommodations
INSERT INTO tbl_accommodations (accommodation_name, description, capacity, price_per_night, availability_status, image_url) VALUES
('Deluxe Ocean Suite', 'Spacious suite with ocean views and king-size bed.', 2, 5500.00, 'available', 'images/bedroom1.jpg'),
('Family Garden Villa', 'Two-bedroom villa with tropical gardens and shared pool.', 5, 8200.00, 'available', 'images/bedroom2.jpg'),
('Standard Twin Room', 'Comfortable room with two single beds.', 2, 2800.00, 'available', 'images/bedroom3.jpg'),
('Premium Cabana', 'Private beachfront cabana with outdoor shower.', 3, 6500.00, 'available', 'images/bedroom4.jpg'),
('Honeymoon Cottage', 'Intimate cottage with jacuzzi and sunset views.', 2, 7500.00, 'unavailable', 'images/bedroom5.jpg'),
('Backpacker Bunk', 'Budget-friendly shared room with lockers.', 4, 1200.00, 'available', 'images/bedroom1.jpg');

-- Amenities
INSERT INTO tbl_amenities (amenity_name, description, price_per_use) VALUES
('Swimming Pool', 'Olympic-size infinity pool.', 0.00),
('Spa & Massage', 'Full-body massage with aromatherapy oils.', 1500.00),
('Kayak Rental', 'Single or tandem kayak.', 800.00),
('Island Hopping Tour', 'Half-day guided tour to three islands.', 2500.00),
('Bicycle Rental', 'Mountain bike for resort trails.', 300.00),
('Bonfire Setup', 'Private beach bonfire with seating.', 1200.00);

-- Packages
INSERT INTO tbl_packages (package_name, description, price, inclusion_details) VALUES
('Weekend Getaway', 'Quick escape from the city.', 12000.00, '2 nights Deluxe Ocean Suite, breakfast buffet, pool access, welcome drinks'),
('Family Fun Bundle', 'Everything for a memorable family stay.', 25000.00, '3 nights Family Garden Villa, daily breakfast, kids activities, island hopping tour, bicycle rental'),
('Romantic Retreat', 'An intimate experience for couples.', 20000.00, '2 nights Honeymoon Cottage, couples spa, private dinner on the beach, bonfire setup'),
('Adventure Package', 'For thrill-seekers and outdoor enthusiasts.', 15000.00, '2 nights Premium Cabana, kayak rental, island hopping, bicycle rental, bonfire');

-- Package accommodations
INSERT INTO tbl_package_accommodations (package_id, accommodation_id) VALUES
(1, 1),
(2, 2),
(3, 5),
(4, 4);

-- Package amenities
INSERT INTO tbl_package_amenities (package_id, amenity_id) VALUES
(1, 1),
(2, 4),
(2, 5),
(3, 2),
(3, 6),
(4, 3),
(4, 4),
(4, 5),
(4, 6);

-- Reservations (sample data)
INSERT INTO tbl_reservations (user_id, accommodation_id, check_in_date, check_out_date, total_price, reservation_status) VALUES
(3, 1, '2026-06-01', '2026-06-03', 11000.00, 'pending'),
(3, 3, '2026-07-10', '2026-07-12', 5600.00, 'approved'),
(4, 2, '2026-06-15', '2026-06-18', 24600.00, 'pending');

-- Logs (sample data)
INSERT INTO tbl_logs (user_id, action, datetime) VALUES
(1, 'Logged In', NOW()),
(3, 'Created Reservation #1 for Deluxe Ocean Suite', NOW()),
(3, 'Created Reservation #2 for Standard Twin Room', NOW()),
(4, 'Created Reservation #3 for Family Garden Villa', NOW());
