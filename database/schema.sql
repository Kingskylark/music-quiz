CREATE DATABASE IF NOT EXISTS music_quiz_db;
USE music_quiz_db;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    nickname VARCHAR(50),
    registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    score INT DEFAULT 0,
    total_time INT DEFAULT 0,  -- in seconds
    status ENUM('not_started', 'in_progress', 'completed') DEFAULT 'not_started',
    ip_address VARCHAR(45),
    session_id VARCHAR(100) UNIQUE,
    INDEX idx_session (session_id),
    INDEX idx_score (score DESC, total_time ASC)
);

-- Questions table
CREATE TABLE questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_text TEXT NOT NULL,
    option_a VARCHAR(255) NOT NULL,
    option_b VARCHAR(255) NOT NULL,
    option_c VARCHAR(255) NOT NULL,
    option_d VARCHAR(255) NOT NULL,
    correct_option ENUM('A', 'B', 'C', 'D') NOT NULL,
    category VARCHAR(50) DEFAULT 'General',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_category (category)
);

-- Answers table
CREATE TABLE answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    question_id INT NOT NULL,
    selected_option ENUM('A', 'B', 'C', 'D'),
    is_correct BOOLEAN DEFAULT FALSE,
    time_taken INT,  -- in seconds
    answered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_question (question_id)
);

-- Admin table
CREATE TABLE admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,  -- hashed password
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin (username: admin, password: admin123)
INSERT INTO admin (username, password) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Insert sample questions (for testing)
INSERT INTO questions (question_text, option_a, option_b, option_c, option_d, correct_option, category) VALUES
('Who is known as the "King of Pop"?', 'Elvis Presley', 'Michael Jackson', 'Prince', 'James Brown', 'B', 'Pop'),
('Which artist released the album "Thriller"?', 'Whitney Houston', 'Madonna', 'Michael Jackson', 'Prince', 'C', 'Pop'),
('What year was Hip-Hop born?', '1973', '1979', '1982', '1985', 'A', 'Hip-Hop'),
('Who is the pioneer of Afrobeats?', 'Wizkid', 'Burna Boy', 'Fela Kuti', 'Davido', 'C', 'Afrobeats'),
('Complete the lyrics: "I got 99 problems but..."', 'a dollar ain''t one', 'a friend ain''t one', 'a pitch ain''t one', 'a switch ain''t one', 'A', 'Hip-Hop');