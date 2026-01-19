# Database Connection Leak Simulation

A Docker-based project that demonstrates a PostgreSQL connection leak in PHP - a common performance issue where connections aren't properly closed after use.

## Project Overview

This project simulates a real-world database connection leak scenario:

- **PHP Application** (`app/index.php`): Opens a PostgreSQL connection but intentionally never closes it
- **PostgreSQL Database**: Configured with a limited connection pool (10 connections max)
- **Intentional Bug**: The PHP script holds connections open for 30 seconds without closing them, demonstrating how repeated requests exhaust the connection pool

## Project Structure

```
thedbapp/
├── docker-compose.yml    # Docker Compose configuration
├── Dockerfile            # PHP 8.2 with PostgreSQL extensions
├── app/
│   └── index.php         # PHP script that leaks connections
└── README.md             # This file
```

## Prerequisites

- Docker and Docker Compose installed
- WSL2 (if on Windows)

## Starting the Container

1. **Build and start the containers:**
   ```bash
   docker-compose up --build -d
   ```

2. **Verify services are running:**
   ```bash
   docker-compose ps
   ```

3. **Access the PHP application:**
   ```bash
   curl http://localhost:8080/index.php
   ```

## How to Detect the Connection Leak Error

### Simulate the Connection Leak

Run multiple concurrent requests to exhaust the connection pool (set to 10 max):

```bash
for i in {1..15}; do curl http://localhost:8080/index.php > /dev/null 2>&1 & done
wait
```

This spawns 15 concurrent requests. Each request holds a connection open for 30 seconds. After ~10 requests, subsequent requests will fail because no connections are available.

### Check Database Logs

View PostgreSQL error logs for connection rejections:

```bash
docker-compose logs db
```

Look for messages like:
- `FATAL: too many connections`
- `FATAL: sorry, too many clients already`

### Check Application Logs

View PHP error logs and custom logging:

```bash
docker-compose logs app
```

Look for messages like:
- `Request received, attempting to connect...`
- `Connection successful!`
- `ERROR: Could not connect to database`
- `Script ending`

### Real-time Monitoring

Watch logs as requests come in:

```bash
# Terminal 1: Watch database logs
docker-compose logs -f db

# Terminal 2: Run the concurrent requests
for i in {1..15}; do curl http://localhost:8080/index.php & done
wait
```

## Understanding the Bug

The bug in `app/index.php`:

```php
$dbconn = @pg_connect($conn_str);

if ($dbconn) {
    echo "Connection Successful! <br>";
    sleep(30);
    // BUG: pg_close($dbconn) is never called!
    // Connection remains open until PHP script ends
}
```

### Why This Is a Problem

1. Each request opens 1 connection
2. The connection stays open for 30 seconds
3. After 10 concurrent requests, the pool is exhausted
4. Subsequent requests cannot connect and fail with `FATAL: too many connections`
5. In production, this causes service degradation or complete failure

## How to Fix the Bug

Add a proper connection close:

```php
if ($dbconn) {
    echo "Connection Successful! <br>";
    sleep(30);
    pg_close($dbconn);  // ← Fix: Close the connection
}
```

## Database Configuration

From `docker-compose.yml`:
- **Image**: `postgres:latest`
- **User**: `support_user`
- **Password**: `password123`
- **Database**: `support_db`
- **Max Connections**: `10` (configured with `max_connections=10`)
- **Port**: `5432` (internal), mapped to `5432` on host

## Stopping the Container

```bash
docker-compose down
```

Remove volumes as well (if needed):
```bash
docker-compose down -v
```

## Additional Commands

View all running containers:
```bash
docker-compose ps
```

Restart services:
```bash
docker-compose restart
```

Access PostgreSQL directly:
```bash
docker-compose exec db psql -U support_user -d support_db
```

## References

- [PostgreSQL Connection Management](https://www.postgresql.org/docs/current/sql-syntax.html)
- [PHP PostgreSQL Extension](https://www.php.net/manual/en/ref.pgsql.php)
- [Docker Compose Documentation](https://docs.docker.com/compose/)
