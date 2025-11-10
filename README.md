# Master to master config
1. Start fresh
```bash
docker compose down -v
docker compose build # CAn be omitted if starting everything for the first time
docker compose up -d
```

2. Create the replication user on both servers
```sql
-- mysql1
CREATE USER IF NOT EXISTS 'repl'@'%' IDENTIFIED BY 'replpass'; 
GRANT REPLICATION SLAVE ON *.* TO 'repl'@'%'; 
FLUSH PRIVILEGES;

-- mysql2
CREATE USER IF NOT EXISTS 'repl'@'%' IDENTIFIED BY 'replpass'; 
GRANT REPLICATION SLAVE ON *.* TO 'repl'@'%'; 
FLUSH PRIVILEGES;
```

3. Configure each server to replicate from the other using GTID auto-positioning:
```sql
-- on mysql_db_1 -> replicate from mysql_db_2
CHANGE MASTER TO SOURCE_HOST='mysql_db_2', 
    SOURCE_USER='repl',
    SOURCE_PASSWORD='replpass',
    SOURCE_AUTO_POSITION=1; 

START REPLICA;

-- on mysql_db_2 -> replicate from mysql_db_1
CHANGE MASTER TO SOURCE_HOST='mysql_db_1', 
    SOURCE_USER='repl',
    SOURCE_PASSWORD='replpass',
    SOURCE_AUTO_POSITION=1; 

START REPLICA;
```