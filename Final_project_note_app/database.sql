
-- =====================================================
-- DATABASE: SEEDGROWTHNOTE
-- MySQL 8.4.3
-- =====================================================

CREATE DATABASE IF NOT EXISTS seedgrowthnote
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE seedgrowthnote;

-- =====================================================
-- 1. USERS
-- =====================================================
CREATE TABLE users (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    display_name VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    avatar_path VARCHAR(255) NULL,
    email_verified_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT uq_users_email UNIQUE (email)
) ENGINE=InnoDB;

-- =====================================================
-- 2. EMAIL_VERIFICATION_TOKENS
-- =====================================================
CREATE TABLE email_verification_tokens (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    token_hash VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_email_tokens_user
        FOREIGN KEY (user_id)
        REFERENCES users(id),

    CONSTRAINT uq_email_token UNIQUE (token_hash),

    INDEX idx_email_tokens_user (user_id)
) ENGINE=InnoDB;

-- =====================================================
-- 3. PASSWORD_RESETS
-- =====================================================
CREATE TABLE password_resets (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    token_hash VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_password_resets_user
        FOREIGN KEY (user_id)
        REFERENCES users(id),

    CONSTRAINT uq_password_reset_token UNIQUE (token_hash),

    INDEX idx_password_resets_user (user_id)
) ENGINE=InnoDB;

-- =====================================================
-- 4. USER_PREFERENCES
-- =====================================================
CREATE TABLE user_preferences (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    font_size VARCHAR(255) NULL,
    note_color VARCHAR(255) NULL,
    theme VARCHAR(255) NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_preferences_user
        FOREIGN KEY (user_id)
        REFERENCES users(id),

    CONSTRAINT uq_preferences_user UNIQUE (user_id)
) ENGINE=InnoDB;

-- =====================================================
-- 5. NOTES
-- =====================================================
CREATE TABLE notes (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    owner_id BIGINT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NULL,
    is_pinned BOOLEAN DEFAULT FALSE,
    pinned_at DATETIME NULL,
    is_password_protected BOOLEAN DEFAULT FALSE,
    note_password_hash VARCHAR(255) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_notes_owner
        FOREIGN KEY (owner_id)
        REFERENCES users(id),

    INDEX idx_notes_owner (owner_id),
    INDEX idx_notes_updated (updated_at)
) ENGINE=InnoDB;

-- =====================================================
-- 6. LABELS
-- =====================================================
CREATE TABLE labels (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_labels_user
        FOREIGN KEY (user_id)
        REFERENCES users(id),

    INDEX idx_labels_user (user_id)
) ENGINE=InnoDB;

-- =====================================================
-- 7. NOTE_LABELS
-- Bang trung gian NOTES va LABELS
-- =====================================================
CREATE TABLE note_labels (
    note_id BIGINT NOT NULL,
    label_id BIGINT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (note_id, label_id),

    CONSTRAINT fk_note_labels_note
        FOREIGN KEY (note_id)
        REFERENCES notes(id),

    CONSTRAINT fk_note_labels_label
        FOREIGN KEY (label_id)
        REFERENCES labels(id),

    INDEX idx_note_labels_label (label_id)
) ENGINE=InnoDB;

-- =====================================================
-- 8. ATTACHMENTS
-- =====================================================
CREATE TABLE attachments (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    note_id BIGINT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    mime_type VARCHAR(255) NULL,
    file_size BIGINT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_attachments_note
        FOREIGN KEY (note_id)
        REFERENCES notes(id),

    INDEX idx_attachments_note (note_id)
) ENGINE=InnoDB;

-- =====================================================
-- 9. NOTE_SUMMARIES
-- =====================================================
CREATE TABLE note_summaries (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    note_id BIGINT NOT NULL,
    summary TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_note_summaries_note
        FOREIGN KEY (note_id)
        REFERENCES notes(id),

    INDEX idx_note_summaries_note (note_id)
) ENGINE=InnoDB;

-- =====================================================
-- 10. NOTE_SHARES
-- =====================================================
CREATE TABLE note_shares (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    note_id BIGINT NOT NULL,
    recipient_id BIGINT NOT NULL,
    permission VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    revoked_at DATETIME NULL,

    CONSTRAINT fk_note_shares_note
        FOREIGN KEY (note_id)
        REFERENCES notes(id),

    CONSTRAINT fk_note_shares_recipient
        FOREIGN KEY (recipient_id)
        REFERENCES users(id),

    INDEX idx_note_shares_note (note_id),
    INDEX idx_note_shares_recipient (recipient_id)
) ENGINE=InnoDB;

-- =====================================================
-- 11. NOTIFICATIONS
-- =====================================================
CREATE TABLE notifications (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    note_share_id BIGINT NOT NULL,
    type VARCHAR(255) NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_notifications_user
        FOREIGN KEY (user_id)
        REFERENCES users(id),

    CONSTRAINT fk_notifications_note_share
        FOREIGN KEY (note_share_id)
        REFERENCES note_shares(id),

    INDEX idx_notifications_user (user_id),
    INDEX idx_notifications_note_share (note_share_id)
) ENGINE=InnoDB;

-- =====================================================
-- 12. AI_QUERIES
-- =====================================================
CREATE TABLE ai_queries (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    question TEXT NOT NULL,
    answer TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_ai_queries_user
        FOREIGN KEY (user_id)
        REFERENCES users(id),

    INDEX idx_ai_queries_user (user_id)
) ENGINE=InnoDB;

-- =====================================================
-- 13. AI_QUERY_NOTES
-- Bang trung gian AI_QUERIES va NOTES
-- =====================================================
CREATE TABLE ai_query_notes (
    ai_query_id BIGINT NOT NULL,
    note_id BIGINT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (ai_query_id, note_id),

    CONSTRAINT fk_ai_query_notes_query
        FOREIGN KEY (ai_query_id)
        REFERENCES ai_queries(id),

    CONSTRAINT fk_ai_query_notes_note
        FOREIGN KEY (note_id)
        REFERENCES notes(id),

    INDEX idx_ai_query_notes_note (note_id)
) ENGINE=InnoDB;
