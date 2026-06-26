-- 1. Users Table Configuration
CREATE TABLE IF NOT EXISTS users (
    id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    activation_token VARCHAR(64) NULL,
    is_active BOOLEAN DEFAULT FALSE,
    notify_on_comment BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Snapshots Workspace Table (Tracks Composited Graphic Images)
CREATE TABLE IF NOT EXISTS snapshots (
    id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    user_id INT NOT NULL,
    storage_path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 3. Comments Social Interaction Table
CREATE TABLE IF NOT EXISTS comments (
    id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    snapshot_id INT NOT NULL,
    user_id INT NOT NULL,
    comment_text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_snapshot FOREIGN KEY (snapshot_id) REFERENCES snapshots(id) ON DELETE CASCADE,
    CONSTRAINT fk_commenter FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 4. Likes Constraint Table
CREATE TABLE IF NOT EXISTS likes (
    id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    snapshot_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT unique_user_snapshot_like UNIQUE (user_id, snapshot_id),
    CONSTRAINT fk_snapshot_like FOREIGN KEY (snapshot_id) REFERENCES snapshots(id) ON DELETE CASCADE,
    CONSTRAINT fk_user_like FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
