#!/bin/bash
set -e

mysql -u root -p"${MYSQL_ROOT_PASSWORD}" <<-EOSQL
    CREATE DATABASE IF NOT EXISTS \`khelsutra\`;
    CREATE DATABASE IF NOT EXISTS \`khelsutra_test\`;
    GRANT ALL PRIVILEGES ON \`khelsutra\`.* TO '${MYSQL_USER}'@'%';
    GRANT ALL PRIVILEGES ON \`khelsutra_test\`.* TO '${MYSQL_USER}'@'%';
    FLUSH PRIVILEGES;
EOSQL

echo "Importing baseline dump into khelsutra..."
mysql -u root -p"${MYSQL_ROOT_PASSWORD}" khelsutra < /docker-entrypoint-initdb.d/khelsutra.sql

echo "Importing baseline dump into khelsutra_test..."
mysql -u root -p"${MYSQL_ROOT_PASSWORD}" khelsutra_test < /docker-entrypoint-initdb.d/khelsutra.sql
