Quick run:
```bash
chmod +x run.sh
./run.sh
```

# Master to master config
1. Start fresh
```bash
docker compose down -v
docker compose build # CAn be omitted if starting everything for the first time
docker compose up -d
```
2. a Create heath-check user for HAProxy
```sql
CREATE USER IF NOT EXISTS 'haproxy_check'@'%' IDENTIFIED WITH mysql_native_password BY 'checkpass';
GRANT USAGE ON *.* TO 'haproxy_check'@'%';
FLUSH PRIVILEGES;
```

3. Create the replication user on both servers and configure replication
```sql
-- mysql1
CREATE USER IF NOT EXISTS 'repl'@'%' IDENTIFIED WITH mysql_native_password BY 'replpass';
GRANT REPLICATION SLAVE ON *.* TO 'repl'@'%';
FLUSH PRIVILEGES;

CHANGE MASTER TO 
    MASTER_HOST='mysql_db_2', 
    MASTER_USER='repl',
    MASTER_PASSWORD='replpass',
    MASTER_AUTO_POSITION=1;
START SLAVE;

-- mysql2
CREATE USER IF NOT EXISTS 'repl'@'%' IDENTIFIED WITH mysql_native_password BY 'replpass';
GRANT REPLICATION SLAVE ON *.* TO 'repl'@'%';
FLUSH PRIVILEGES;

CHANGE MASTER TO 
    MASTER_HOST='mysql_db_1', 
    MASTER_USER='repl',
    MASTER_PASSWORD='replpass',
    MASTER_AUTO_POSITION=1;
START SLAVE;
```

---

# Notes

- Only create your application schema and user once (on one node). The official MySQL image will auto-create `MYSQL_USER`/`MYSQL_PASSWORD` during init if those envvars are set — do NOT set them on the replica node (or import the schema on the replica) or the initial `CREATE USER` will conflict when it later replicates.
- In this repo we use `mysql_db_1` as the seed: import `serverA/base.sql` there and leave `mysql_db_2` without the `MYSQL_USER`/`MYSQL_PASSWORD` envvars so the `CREATE USER` event is replicated cleanly.

Verify
- Check replication status on each node:
```bash
docker exec -i mysql_db_1 mysql -h127.0.0.1 -P3306 -uroot -proot -e "SHOW SLAVE STATUS\G"
docker exec -i mysql_db_2 mysql -h127.0.0.1 -P3306 -uroot -proot -e "SHOW SLAVE STATUS\G"
# look for Slave_IO_Running: Yes and Slave_SQL_Running: Yes and Last_SQL_Errno: 0
```

If you prefer to do the steps manually, follow the SQL in step 3 but make sure the application schema/user is created only once (on the seed node) before you CHANGE MASTER on the other node.