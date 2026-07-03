-- Drop tables if they exist to allow clean structural resets
DROP TABLE IF EXISTS notifications CASCADE;
DROP TABLE IF EXISTS comments CASCADE;
DROP TABLE IF EXISTS likes CASCADE;
DROP TABLE IF EXISTS posts CASCADE;
DROP TABLE IF EXISTS users CASCADE;

-- 1. USER PROFILE IDENTITY ACCREDITATION TABLE
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    verification_token VARCHAR(100),
    is_verified BOOLEAN DEFAULT FALSE,
    reset_token VARCHAR(100) DEFAULT NULL,
    reset_expires_at TIMESTAMP WITH TIME ZONE DEFAULT NULL,
    notify_on_comment BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Create optimization lookup indexes for authentication vectors
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_users_email ON users(email);


-- 2. MEDIA COMPOSITION LOGISTICS WORKSPACE TABLE
CREATE TABLE posts (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_post_user FOREIGN KEY (user_id) 
        REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_posts_user ON posts(user_id);


-- 3. INTERACTION TRANSACTION LIKES MATRIX TABLE
CREATE TABLE likes (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL,
    post_id INT NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uk_user_post_like UNIQUE (user_id, post_id),
    CONSTRAINT fk_like_user FOREIGN KEY (user_id) 
        REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_like_post FOREIGN KEY (post_id) 
        REFERENCES posts(id) ON DELETE CASCADE
);


-- 4. SOCIAL INTERACTION TEXT COMMENTARY TABLE
CREATE TABLE comments (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL,
    post_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_comment_user FOREIGN KEY (user_id) 
        REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_comment_post FOREIGN KEY (post_id) 
        REFERENCES posts(id) ON DELETE CASCADE
);

CREATE INDEX idx_comments_post ON comments(post_id);


-- 5. REALTIME transactional USER NOTIFICATION PIPELINE TABLE
CREATE TABLE notifications (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL,              -- Target entity receiving the alert
    sender_id INT NOT NULL,            -- Activity originator trigger entity
    post_id INT NOT NULL,              -- Subject entity scope connection
    type VARCHAR(20) NOT NULL,         -- 'LIKE' or 'COMMENT'
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notification_user FOREIGN KEY (user_id) 
        REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_notification_sender FOREIGN KEY (sender_id) 
        REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_notification_post FOREIGN KEY (post_id) 
        REFERENCES posts(id) ON DELETE CASCADE
);

CREATE INDEX idx_notifications_user_unread ON notifications(user_id) WHERE is_read = FALSE;
