# framework

## Running with Docker

This project is set up to run with Docker using PHP 8.2 (FPM, Alpine) and Composer for dependency management. The Docker setup installs required PHP extensions (intl, zip, pdo, pdo_mysql, gd, apcu, opcache) and configures PHP using custom `php.ini` and `opcache.ini` files from the `docker/php/` directory. For development, Xdebug is available via a separate build target.

### Requirements
- Docker (latest)
- Docker Compose (latest)

### Environment Variables
- The application supports environment variables via `.env` or `.sample.env`. You may need to copy `.sample.env` to `.env` and adjust values as needed before building.

### Build and Run
1. (Optional) Copy `.sample.env` to `.env` and configure your environment variables.
2. Build and start the application:
   ```sh
   docker compose up --build
   ```

### Service Details
- **php-app**
  - Runs PHP-FPM (port 9000 exposed internally)
  - Designed to be used behind a web server (nginx/apache) proxying to PHP-FPM
  - Uses a non-root user for security
  - Storage directories (`storage/logs`, `storage/cache`, `storage/uploads`) are writable by the app

### Special Configuration
- PHP configuration files are located in `docker/php/` and are automatically copied into the container.
- For development with Xdebug, use the `dev` build target in the Dockerfile.
- No web server is included by default; you can add nginx or apache as needed, or use your own reverse proxy.

### Ports
- `9000` (PHP-FPM) exposed internally by the `php-app` service

---
