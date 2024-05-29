CREATE DATABASE IF NOT EXISTS kahuna;

USE kahuna;

CREATE TABLE User(
    id              INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    email           VARCHAR(255) NOT NULL,
    password        VARCHAR(255) NOT NULL,
    accessLevel     CHAR(10) NOT NULL DEFAULT 'user'

);

CREATE TABLE AccessToken(
    id              INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    userId          INT NOT NULL,
    token           VARCHAR(255) NOT NULL,
    birth           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT c_accesstoken_user
        FOREIGN KEY(userId) REFERENCES User(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS Product (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    serial VARCHAR(255) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    warrantyLength INT NOT NULL
);

INSERT INTO Product (serial, name, warrantyLength) VALUES
('KHWM8199911', 'CombiSpin Washing Machine', 2),
('KHWM8199912', 'CombiSpin + Dry Washing Machine', 2),
('KHMW789991', 'CombiGrill Microwave', 1),
('KHWP890001', 'K5 Water Pump', 5),
('KHWP890002', 'K5 Heated Water Pump', 5),
('KHSS988881', 'Smart Switch Lite', 2),
('KHSS988882', 'Smart Switch Pro', 2),
('KHSS988883', 'Smart Switch Pro V2', 2),
('KHHM89762', 'Smart Heated Mug', 1),
('KHSB0001', 'Smart Bulb 001', 1);


CREATE TABLE IF NOT EXISTS RegisteredProducts (
    ID INT PRIMARY KEY AUTO_INCREMENT,
    userId INT NOT NULL,
    serialNumber VARCHAR(255) NOT NULL,
    FOREIGN KEY (userId) REFERENCES User(id),
    FOREIGN KEY (serialNumber) REFERENCES Product(serial)
);

CREATE TABLE IF NOT EXISTS Transaction ( 
  transaction_id INT PRIMARY KEY AUTO_INCREMENT,
  purchase_date DATE NOT NULL,
  user_id INT NOT NULL,
  product_id INT NOT NULL, 
  warranty_start_date DATE,
  warranty_end_date DATE,
  FOREIGN KEY (user_id) REFERENCES User(id),
  FOREIGN KEY (product_id) REFERENCES Product(id)
);

CREATE TABLE IF NOT EXISTS SupportTicket (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description VARCHAR(100) NOT NULL  
);

CREATE TABLE IF NOT EXISTS ReplyTicket (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    description VARCHAR(100) NOT NULL,
    FOREIGN KEY (ticket_id) REFERENCES SupportTicket(id)
);