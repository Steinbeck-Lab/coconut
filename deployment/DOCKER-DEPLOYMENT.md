# NMR Platform Docker Deployment

This document describes how to deploy the NMR Platform using Docker with FrankenPHP.

## 🏗️ Architecture

- **Base Image**: FrankenPHP (modern PHP application server)
- **PHP Version**: 8.3 with all required extensions
- **Frontend**: Node.js 18 for asset building
- **Process Management**: Supervisor
- **Services**: FrankenPHP web server, Laravel scheduler, queue workers
- **Configuration**: Organized in `deployment/` folder for easy maintenance

## 📋 Prerequisites

- Docker & Docker Compose
- Composer authentication credentials for private repositories

## 🚀 Quick Start

### 1. Set Composer Authentication

```bash
export COMPOSER_AUTH_JSON='{"http-basic":{"your-repo.com":{"username":"your-username","password":"your-token"}}}'
```

### 2. Build and Deploy

```bash
# Build the image
./deployment/deploy.sh

# Or build with a specific tag
./deployment/deploy.sh v1.0.0

# Build and push to registry
./deployment/deploy.sh latest --push
```

### 3. Run with Docker Compose

```bash
docker-compose -f docker-compose.prod.yml up -d
```

## 🔧 Manual Docker Build

```bash
docker build \
  --build-arg COMPOSER_AUTH="$COMPOSER_AUTH_JSON" \
  --build-arg WWWUSER=1000 \
  --build-arg WWWGROUP=1000 \
  --build-arg TZ=UTC \
  --tag nfdi4chem/nmr-platform:latest \
  .
```

## 🏃‍♂️ Running the Container

### Basic Run

```bash
docker run -p 8000:8000 \
  -e APP_KEY=your-app-key \
  -e DB_HOST=your-db-host \
  -e DB_PASSWORD=your-db-password \
  nfdi4chem/nmr-platform:latest
```

### With Environment File

```bash
docker run -p 8000:8000 \
  --env-file .env.production \
  nfdi4chem/nmr-platform:latest
```

## 🌐 Production Deployment

The `docker-compose.prod.yml` includes:

- **Traefik** load balancer with SSL/TLS
- **PostgreSQL** database
- **Redis** for caching and sessions
- **Automatic HTTPS** with Let's Encrypt

### Environment Variables

Create a `.env.production` file:

```env
APP_NAME="NMR Platform"
APP_ENV=production
APP_KEY=base64:your-generated-key
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=pgsql
DB_HOST=pgsql
DB_PORT=5432
DB_DATABASE=nmr_platform
DB_USERNAME=nmr_user
DB_PASSWORD=secure-password

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

# Run migrations on startup
RUN_MIGRATIONS=true
```

## 🔍 Health Checks

The container includes a built-in health check:

```bash
# Check container health
docker ps

# Manual health check
curl http://localhost:8000/up
```

## 📊 Monitoring

Supervisor manages these processes:

- **FrankenPHP**: Web server on port 8000
- **Scheduler**: Laravel task scheduler
- **Queue Workers**: Background job processing

View logs:

```bash
# All logs
docker logs container-name

# Follow logs
docker logs -f container-name

# Supervisor status
docker exec container-name supervisorctl status
```

## 🛠️ Development vs Production

### Development
- Uses Laravel Sail with separate services
- Hot reloading for frontend assets
- Debug mode enabled

### Production
- Single optimized container
- Cached configurations
- Production-ready FrankenPHP server
- Supervisor process management

## 📁 Configuration Files

The Docker setup uses organized configuration files in the `deployment/` folder:

- **`deployment/Caddyfile`**: FrankenPHP web server configuration
- **`deployment/supervisord.conf`**: Process management configuration
- **`deployment/start-container`**: Container startup script
- **`deployment/healthcheck`**: Health check script
- **`deployment/deploy.sh`**: Deployment automation script
- **`deployment/acme.json`**: Let's Encrypt SSL certificate storage

This structure makes it easy to:
- Edit configurations without rebuilding
- Version control deployment settings
- Maintain consistency across environments

## 🔒 Security Features

- Non-root user for application processes
- Security headers (CSP, XSS protection, etc.)
- Gzip compression
- Optimized PHP configuration
- Secure file permissions

## 🐛 Troubleshooting

### Build Issues

1. **Composer authentication errors**:
   ```bash
   # Verify COMPOSER_AUTH_JSON is set correctly
   echo $COMPOSER_AUTH_JSON | jq .
   ```

2. **Missing PHP extensions**:
   - All required extensions are included in the Dockerfile
   - Check logs for specific error messages

3. **Frontend build failures**:
   - Ensure Node.js dependencies are properly installed
   - Check for Vite configuration issues

### Runtime Issues

1. **Database connection errors**:
   - Verify database host and credentials
   - Ensure database is accessible from container

2. **Permission errors**:
   - Check file ownership in storage directories
   - Verify user/group IDs match

3. **Queue worker failures**:
   - Check database connectivity
   - Verify Redis connection for queue driver

## 📈 Performance Tuning

- **FrankenPHP**: Uses worker mode for optimal performance
- **OPcache**: Enabled for PHP bytecode caching
- **Composer**: Optimized autoloader
- **Laravel**: Cached configurations and routes

## 🔄 Updates

To update the application:

1. Build new image with updated code
2. Tag with version number
3. Update docker-compose.yml
4. Rolling update with zero downtime

```bash
# Build new version
./deployment/deploy.sh v1.1.0 --push

# Update compose file
sed -i 's/nmr-platform:latest/nmr-platform:v1.1.0/' docker-compose.prod.yml

# Deploy update
docker-compose -f docker-compose.prod.yml up -d
``` 