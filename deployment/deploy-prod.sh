#!/bin/bash

set -e

# Print timestamp at the start of the script
echo "🚀 ==== Script started at: $(date '+%Y-%m-%d %H:%M:%S') ==== "

APP_IMAGE="nfdi4chem/coconut:latest"
WORKER_IMAGE="nfdi4chem/coconut:latest"
PROJECT_ROOT=$(dirname "$(dirname "$(realpath "$0")")")
APP_COMPOSE_FILE="$PROJECT_ROOT/deployment/docker-compose.prod.yml"
ENV_FILE="$PROJECT_ROOT/.env"
NEW_CONTAINER_ID=""
BACKUP_DIR="./backups"
BUILD=false
DEPLOY=false
BACKUP=false

# === Load environment ===

cd "$PROJECT_ROOT"
echo "Project root: $PROJECT_ROOT"

set -a
source .env
set +a

export COMPOSE_PROJECT_NAME=coconut

# === Functions ===

# Utility functions
log() {
    echo "ℹ️  $1"
}

error() {
    echo "❌ Error: $1" >&2
    exit 1
}

success() {
    echo "✅ $1"
}

# Check requirements
check_requirements() {
    log "Checking requirements..."
    
    [[ -f "$APP_COMPOSE_FILE" ]] || error "Docker compose file $APP_COMPOSE_FILE not found"
    [[ -f "$ENV_FILE" ]] || error "Environment file $ENV_FILE not found"
    
    command -v docker >/dev/null 2>&1 || error "Docker is not installed"
    command -v docker-compose >/dev/null 2>&1 || error "Docker Compose is not installed"
    
    success "Requirements check passed"
}

# Wait for new container to pass health check
wait_for_health() {
    echo "⏳ Waiting for new container to pass health check (up to 10 retries)..."
    for i in {1..10}; do
        if check_container_health; then
            echo "✅ Container is healthy."
            return 0
        else
            echo "Retry $i/10: Waiting 60s..."
            sleep 60
        fi
    done
    return 1
}

# Check if the container is healthy
check_container_health() {
    if [[ -z "$NEW_CONTAINER_ID" ]]; then
        return 1
    fi
    HEALTH=$(docker inspect --format='{{json .State.Health.Status}}' "$NEW_CONTAINER_ID" 2>/dev/null || echo '"unhealthy"')
    [[ "$HEALTH" == *"healthy"* ]]
}

# Remove old containers after successful deployment
remove_old_containers() {
    local name_prefix=$1
    echo "🧼 Removing old ${name_prefix} container(s)..."

    container_ids=$(docker ps -a --filter "name=${name_prefix}" --format "{{.ID}}")
    sorted_container_ids=$(echo "$container_ids" | xargs docker inspect --format='{{.Created}} {{.ID}}' | sort | awk '{print $2}')
    oldest_container_id=$(echo "$sorted_container_ids" | head -n 1)

    if [ -z "$oldest_container_id" ]; then
        echo "❌ No containers found with name prefix: ${name_prefix}"
        exit 1
    fi

    docker stop "$oldest_container_id"
    cleanup

    echo "✅ Deleted old container ID: $oldest_container_id"
}

# Cleanup
cleanup() {
    echo "Cleaning up..."
    
    # Remove stopped containers
    docker container prune -f >/dev/null 2>&1 || true

    # Remove unused images
    docker image prune -f >/dev/null 2>&1 || true
    
    # Keep only last 5 backups
    if [[ -d "$BACKUP_DIR" ]]; then
        find "$BACKUP_DIR" -name "*.sql" -type f | sort -r | tail -n +6 | xargs -r rm -f
    fi
    
    echo "Cleanup completed"
}

# Check if app is responding
check_app_health(){
    echo "🏥 Checking application health..."
    if docker compose -p "$COMPOSE_PROJECT_NAME" -f "$APP_COMPOSE_FILE" exec -T app curl -f http://localhost:8000/up > /dev/null 2>&1; then
        echo "✅ Application is healthy!"
    else
        echo "❌ Application health check failed"
        echo "📋 Showing app logs:"
        docker compose -f "$APP_COMPOSE_FILE" logs app --tail=50
        exit 1
    fi
}

# Deploy app and worker if new image is available
deploy_service() {
    echo "Starting zero-downtime deployment..."
    check_requirements

    # Pull the image and check if it's new
    if [ "$(docker pull "$APP_IMAGE" | grep -c "Status: Image is up to date")" -eq 0 ]; then
        echo "📦 New image available for app and worker."

        backup_database 

        # Scale up both app and worker to 2
        docker compose -f "$APP_COMPOSE_FILE" up -d app worker --scale app=2 --scale worker=2 --no-deps --no-recreate

        sleep 10

        # Remove old containers for both services
        remove_old_containers "app"
        remove_old_containers "worker"

        echo "✅ Deployment of app and worker done successfully."

        run_migration_and_clear_cache

        echo "Application is available at: https://coconut.naturalproducts.net/"
    else
        echo "✅ No update for app and worker. Skipping deployment."
    fi
}

build_or_restart_services() {
    if docker compose -f "$APP_COMPOSE_FILE" ps -q | grep -q .; then 
        docker compose -f "$APP_COMPOSE_FILE" down --remove-orphans
    fi
    
    echo "Building app containers..."
    docker compose -f "$APP_COMPOSE_FILE" build --no-cache
    docker compose -f "$APP_COMPOSE_FILE" up -d

    echo "Waiting for database to be ready..."
    sleep 10

    run_migration_and_clear_cache

    cleanup
    echo "Services restarted successfully!"
    echo "Application is available at: https://coconut.naturalproducts.net/"
}

# Create database backup
backup_database() {
    echo "Creating database backup..."
    
    mkdir -p "$BACKUP_DIR"
    local backup_file="$BACKUP_DIR/db_backup_$(date +%Y%m%d_%H%M%S).sql"
    
    if docker compose -p "$COMPOSE_PROJECT_NAME" -f "$APP_COMPOSE_FILE"  exec -T pgsql \
        pg_dump -h localhost -U "${DB_USERNAME}" "${DB_DATABASE}" > "$backup_file" 2>/dev/null; then
        echo "Database backup created: $backup_file"
    else
        echo "Database backup failed. Please check your database connection and credentials."
    fi
}

# Run database seeders
run_migration_and_clear_cache() {
    echo "Running database migration..."
    
    # Run seeders
    echo "Executing Laravel database migration..."
    docker compose -f "$APP_COMPOSE_FILE" exec -T app php artisan migrate --force
    docker compose -f "$APP_COMPOSE_FILE" exec -T app php artisan optimize:clear

    docker compose -f "$APP_COMPOSE_FILE" ps

    echo "Database migration completed successfully"
}

# === Display Help ===
display_help() {
    echo "Usage: $0 [OPTIONS]"
    echo "\nOptions:"
    echo "  --build           Build and deploy the application"
    echo "  --deploy          Perform zero-downtime deployment"
    echo "  --backup          Create a database backup"
    echo "  --restart         Restart services"
    echo "  --help            Display this help message"
    exit 0
}

# === Parse arguments ===
while [[ $# -gt 0 ]]; do
    case $1 in
        --build) BUILD=true; shift ;;
        --deploy) DEPLOY=true; shift ;;
        --backup) BACKUP=true; shift ;;
        --restart) RESTART=true; shift ;;
        --help) HELP=true; shift ;;
        *) echo "Unknown option: $1"; exit 1 ;;
    esac
done

# === Deployment Flow ===
case true in
    $DEPLOY)
        deploy_service
        ;;
    $BUILD)
        build_or_restart_services
        ;;
    $BACKUP)
        backup_database
        ;;
    $RESTART)
        build_or_restart_services
        ;;
    $HELP)
        display_help
        ;;
    *)
        echo "Skipping build and deploy step — please pass at least one argument: \n--build: Build and deploy the application \n--deploy: Perform zero-downtime deployment \n--backup: Create a database backup \n--restart: Restart services. If you are unsure, use the --help flag for guidance."
        ;;
esac

echo "🚀 ==== Script ended at: $(date '+%Y-%m-%d %H:%M:%S') ==== "