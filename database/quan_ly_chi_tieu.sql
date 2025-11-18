-- Quan_Ly_Chi_Tieu Database Schema
-- Personal Finance Management System

-- Create database
CREATE DATABASE IF NOT EXISTS quan_ly_chi_tieu CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE quan_ly_chi_tieu;

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    avatar VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE
);

-- Categories table
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    type ENUM('income', 'expense') NOT NULL,
    color VARCHAR(7) DEFAULT '#007bff',
    icon VARCHAR(50),
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Transactions table
CREATE TABLE transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    description TEXT,
    transaction_date DATE NOT NULL,
    type ENUM('income', 'expense') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- Budgets table
CREATE TABLE budgets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    category_id INT,
    name VARCHAR(100) NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    period ENUM('weekly', 'monthly', 'yearly') DEFAULT 'monthly',
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Savings goals table
CREATE TABLE savings_goals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    target_amount DECIMAL(15,2) NOT NULL,
    current_amount DECIMAL(15,2) DEFAULT 0,
    target_date DATE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Notifications table
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'warning', 'success', 'error') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Danh mục mặc định
INSERT INTO categories (name, type, color, icon) VALUES
('Lương', 'income', '#28a745', 'fas fa-money-bill-wave'),
('Freelance', 'income', '#20c997', 'fas fa-laptop-code'),
('Đầu tư', 'income', '#ffc107', 'fas fa-chart-line'),
('Ăn uống', 'expense', '#dc3545', 'fas fa-utensils'),
('Di chuyển', 'expense', '#6f42c1', 'fas fa-car'),
('Mua sắm', 'expense', '#e83e8c', 'fas fa-shopping-bag'),
('Giải trí', 'expense', '#fd7e14', 'fas fa-film'),
('Tiền điện nước', 'expense', '#17a2b8', 'fas fa-lightbulb'),
('Sức khỏe', 'expense', '#6c757d', 'fas fa-heartbeat'),
('Giáo dục', 'expense', '#007bff', 'fas fa-graduation-cap');

-- Create indexes for better performance
CREATE INDEX idx_transactions_user_date ON transactions(user_id, transaction_date);
CREATE INDEX idx_transactions_category ON transactions(category_id);
CREATE INDEX idx_budgets_user ON budgets(user_id);
CREATE INDEX idx_savings_goals_user ON savings_goals(user_id);
CREATE INDEX idx_notifications_user ON notifications(user_id, is_read);
