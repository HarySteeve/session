    CREATE DATABASE session;

    use session;

    CREATE TABLE sessions (
        id VARCHAR(128) PRIMARY KEY,
        data TEXT,
        last_access TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    );
