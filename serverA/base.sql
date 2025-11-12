USE session_db;

CREATE TABLE sessions (
    id VARCHAR(128) PRIMARY KEY,
    data TEXT,
    last_access TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE test_sync_table (
    id INT AUTO_INCREMENT PRIMARY KEY,
    value VARCHAR(255)
);

-- INSERT INTO test_sync_table (value) VALUES ('from server 1');