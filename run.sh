#!/usr/bin/env bash
set -euo pipefail

docker compose down -v
docker compose build || true
docker compose up -d

wait_for() {
  svc="$1"
  echo "waiting for $svc mysqld..."
  # Use mysqladmin ping over TCP to avoid socket path issues inside the container
  until docker exec "$svc" mysqladmin ping -h127.0.0.1 -uroot -proot --silent >/dev/null 2>&1; do
    echo "waiting for $svc..."
    sleep 1
  done
}

wait_for mysql_db_1
wait_for mysql_db_2
wait_for mysql_db_3

# Create replication user (TCP: 3306 mysql_native_password)
docker exec -i mysql_db_1 mysql -h127.0.0.1 -P3306 -uroot -proot -e "DROP USER IF EXISTS 'repl'@'%'; CREATE USER 'repl'@'%' IDENTIFIED WITH mysql_native_password BY 'replpass'; GRANT REPLICATION SLAVE ON *.* TO 'repl'@'%'; FLUSH PRIVILEGES;"
docker exec -i mysql_db_2 mysql -h127.0.0.1 -P3306 -uroot -proot -e "DROP USER IF EXISTS 'repl'@'%'; CREATE USER 'repl'@'%' IDENTIFIED WITH mysql_native_password BY 'replpass'; GRANT REPLICATION SLAVE ON *.* TO 'repl'@'%'; FLUSH PRIVILEGES;"
docker exec -i mysql_db_3 mysql -h127.0.0.1 -P3306 -uroot -proot -e "DROP USER IF EXISTS 'repl'@'%'; CREATE USER 'repl'@'%' IDENTIFIED WITH mysql_native_password BY 'replpass'; GRANT REPLICATION SLAVE ON *.* TO 'repl'@'%'; FLUSH PRIVILEGES;"

# Copy SQL into primary only to avoid creating the same user twice on both servers
# NOTE: We want the application user and schema created once and then propagated by GTID
docker cp serverA/base.sql mysql_db_1:/tmp/base.sql || true
docker exec -i mysql_db_1 sh -c "mysql -h127.0.0.1 -P3306 -uroot -proot < /tmp/base.sql"

sleep 1

# Ensure test table exists (avoid situation where import finishes slightly later)
echo "waiting for session_db.test_sync_table to exist on mysql_db_1..."
tries=0
until docker exec -i mysql_db_1 mysql -h127.0.0.1 -P3306 -uroot -proot -e "SHOW TABLES FROM session_db LIKE 'test_sync_table'" >/dev/null 2>&1 || [ $tries -ge 15 ]; do
  echo "waiting for table... ($tries)"
  sleep 1
  tries=$((tries+1))
done

# Reset and configure GTID auto-positioning master to master (TCP)
docker exec -i mysql_db_1 mysql -h127.0.0.1 -P3306 -uroot -proot -e "STOP SLAVE; RESET SLAVE ALL; CHANGE MASTER TO MASTER_HOST='mysql_db_2', MASTER_USER='repl', MASTER_PASSWORD='replpass', MASTER_AUTO_POSITION=1; START SLAVE;"
docker exec -i mysql_db_2 mysql -h127.0.0.1 -P3306 -uroot -proot -e "STOP SLAVE; RESET SLAVE ALL; CHANGE MASTER TO MASTER_HOST='mysql_db_3', MASTER_USER='repl', MASTER_PASSWORD='replpass', MASTER_AUTO_POSITION=1; START SLAVE;"
docker exec -i mysql_db_3 mysql -h127.0.0.1 -P3306 -uroot -proot -e "STOP SLAVE; RESET SLAVE ALL; CHANGE MASTER TO MASTER_HOST='mysql_db_1', MASTER_USER='repl', MASTER_PASSWORD='replpass', MASTER_AUTO_POSITION=1; START SLAVE;"

# Status
docker exec -i mysql_db_1 mysql -h127.0.0.1 -P3306 -uroot -proot -e "SHOW SLAVE STATUS\G" || true
docker exec -i mysql_db_2 mysql -h127.0.0.1 -P3306 -uroot -proot -e "SHOW SLAVE STATUS\G" || true
docker exec -i mysql_db_3 mysql -h127.0.0.1 -P3306 -uroot -proot -e "SHOW SLAVE STATUS\G" || true

echo "replication setup script finished"

# Replication test
echo "running simple replication test: insert row on mysql_db_1, check on mysql_db_2"
docker exec -i mysql_db_1 mysql -h127.0.0.1 -P3306 -uroot -proot -e "INSERT INTO session_db.test_sync_table (value) VALUES ('test-from-1');"
try=0
until docker exec -i mysql_db_2 mysql -h127.0.0.1 -P3306 -uroot -proot -e "SELECT id,value FROM session_db.test_sync_table WHERE value='test-from-1'\G" | grep -q "test-from-1" || [ $try -ge 10 ]; do
  echo "waiting for row to replicate to mysql_db_2... ($try)"
  sleep 1
  try=$((try+1))
done
docker exec -i mysql_db_2 mysql -h127.0.0.1 -P3306 -uroot -proot -e "SELECT id,value FROM session_db.test_sync_table WHERE value='test-from-1'\G" || true

until docker exec -i mysql_db_3 mysql -h127.0.0.1 -P3306 -uroot -proot -e "SELECT id,value FROM session_db.test_sync_table WHERE value='test-from-1'\G" | grep -q "test-from-1" || [ $try -ge 10 ]; do
  echo "waiting for row to replicate to mysql_db_3... ($try)"
  sleep 1
  try=$((try+1))
done
docker exec -i mysql_db_3 mysql -h127.0.0.1 -P3306 -uroot -proot -e "SELECT id,value FROM session_db.test_sync_table WHERE value='test-from-1'\G" || true

echo "running simple replication test: insert row on mysql_db_2, check on mysql_db_1"
docker exec -i mysql_db_2 mysql -h127.0.0.1 -P3306 -uroot -proot -e "INSERT INTO session_db.test_sync_table (value) VALUES ('test-from-2');"
try=0
until docker exec -i mysql_db_3 mysql -h127.0.0.1 -P3306 -uroot -proot -e "SELECT id,value FROM session_db.test_sync_table WHERE value='test-from-2'\G" | grep -q "test-from-2" || [ $try -ge 10 ]; do
  echo "waiting for row to replicate to mysql_db_3... ($try)"
  sleep 1
  try=$((try+1))
done
docker exec -i mysql_db_3 mysql -h127.0.0.1 -P3306 -uroot -proot -e "SELECT id,value FROM session_db.test_sync_table WHERE value='test-from-2'\G" || true

until docker exec -i mysql_db_1 mysql -h127.0.0.1 -P3306 -uroot -proot -e "SELECT id,value FROM session_db.test_sync_table WHERE value='test-from-2'\G" | grep -q "test-from-2" || [ $try -ge 10 ]; do
  echo "waiting for row to replicate to mysql_db_1... ($try)"
  sleep 1
  try=$((try+1))
done
docker exec -i mysql_db_1 mysql -h127.0.0.1 -P3306 -uroot -proot -e "SELECT id,value FROM session_db.test_sync_table WHERE value='test-from-2'\G" || true

echo "replication test finished"