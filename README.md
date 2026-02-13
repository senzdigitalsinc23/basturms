# Basturms School Management System

A comprehensive PHP-based school management system for managing students, staff, academics, finances, and administrative operations.

## Features

### Academic Management
- Academic year and term management
- Subject and class management
- Grading schemes and student scores
- Assignment activities and tracking
- Promotion criteria management

### Student Management
- Student enrollment and records
- Student promotions and graduations
- Student financial tracking
- Document management
- Emergency contact information

### Security & Authentication
- JWT-based API authentication
- API key authentication
- Account lockout after failed login attempts (5 attempts, 30-minute lockout)
- Rate limiting on sensitive endpoints
- Role-based access control (RBAC)

### API Documentation
- Interactive Swagger/OpenAPI documentation at `/api/v1/docs`
- Comprehensive API endpoint documentation
- Auto-generated from code annotations

### Additional Features
- Audit logging and security tracking
- Database migrations and seeders
- File uploads and document management
- Backup and restore functionality

## Prerequisites

- PHP 8.2 or higher
- MySQL 5.7+ or MariaDB 10.3+
- Composer
- Apache/Nginx web server
- Node.js (for frontend, if applicable)

### Required PHP Extensions
- PDO
- pdo_mysql
- mbstring
- intl
- zip
- gd
- apcu (optional, for caching)
- opcache (recommended for production)

## Installation

### 1. Clone the Repository
```bash
git clone <repository-url>
cd basturms
```

### 2. Install Dependencies
```bash
composer install
```

### 3. Environment Configuration
Copy the sample environment file and configure your settings:
```bash
cp .sample.env .env
```

Edit `.env` and configure:
```env
# Database
DB_HOST=localhost
DB_NAME=basturms
DB_USER=root
DB_PASS=your_password

# Application
APP_URL=http://localhost:8000
APP_ENV=development

# Security
JWT_SECRET=your-strong-jwt-secret-key-change-this
API_KEY=your-api-key

# Rate Limiting
RATE_LIMIT_MAX=100
RATE_LIMIT_WINDOW=60

# Brute Force Protection
BRUTE_FORCE_MAX=5
BRUTE_FORCE_LOCKOUT=900
```

### 4. Database Setup
Create your database:
```sql
CREATE DATABASE basturms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Run migrations:
```bash
php cli.php migrate
```

Seed the database (optional):
```bash
php cli.php seed
```

### 5. Start the Development Server
```bash
composer serve
# or
php server run
```

The application will be available at `http://localhost:8000`

## API Documentation

Access the interactive API documentation at:
- **Swagger UI**: `http://localhost:8000/api/v1/docs`
- **OpenAPI JSON**: `http://localhost:8000/api/v1/swagger`

### Authentication

The API supports two authentication methods:

#### 1. API Key Authentication
Include in request header:
```
X-API-Key: your-api-key
```

#### 2. JWT Bearer Token
Include in request header:
```
Authorization: Bearer your-jwt-token
```

Get a token by logging in:
```bash
POST /api/v1/login
{
  "email": "user@example.com",
  "password": "password"
}
```

### Rate Limiting

Sensitive endpoints are protected by rate limiting:
- Login: Limited to prevent brute-force attacks
- Registration: Limited to prevent spam
- Deletion endpoints: Limited for security

### Account Lockout Policy

Failed login attempts trigger automatic account lockout:
- **Threshold**: 5 failed attempts
- **Lockout Duration**: 30 minutes
- **Admin Unlock**: Available via `/api/v1/admin/users/unlock`

## Database Schema

See [DATABASE.md](DATABASE.md) for comprehensive database schema documentation including:
- Table descriptions
- Relationships and foreign keys
- Indexes and constraints
- Recent schema changes

## Development

### Running Tests
```bash
composer test
```

### Code Linting
```bash
composer lint
```

### Security Audit
```bash
composer security-audit
```

### Creating Migrations
```bash
php cli.php make:migration create_your_table_name
```

### Creating Seeders
```bash
php cli.php make:seeder YourSeederName
```

## Docker Deployment

### Build and Run
```bash
docker compose up --build
```

### Service Details
- **php-app**: PHP-FPM on port 9000
- Uses Alpine Linux for minimal footprint
- Non-root user for security
- Xdebug available in dev mode

See [Docker Configuration](#running-with-docker) section below for details.

## Security Best Practices

- **Environment Variables**: Never commit `.env` to version control
- **Session Security**: HTTPOnly, Secure, SameSite cookies enabled
- **Database**: Parameterized queries prevent SQL injection
- **HTTPS**: Enforce HTTPS in production
- **Headers**: Security headers (CSP, X-Frame-Options, etc.) configured
- **Input Validation**: All user input sanitized and validated
- **Rate Limiting**: Implemented on sensitive endpoints
- **Account Lockout**: Automatic lockout after failed login attempts

## Logging

Logs are stored in `storage/logs/` with daily rotation:
- **Log Levels**: debug, info, notice, warning, error, critical, alert, emergency
- **Retention**: Last 7 days kept automatically
- **Context**: IP address and contextual data included

Example:
```php
$logger = new Logger();
$logger->info('User logged in', ['user_id' => 123]);
$logger->error('An error occurred', ['exception' => $e]);
```

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

## CLI Commands

The application provides a command-line interface (`cli.php`) for various development and maintenance tasks, including database migrations and seeding.

### Database Migrations

-   **Run all pending migrations:**
    ```sh
    php cli.php migrate
    ```

-   **Rollback the last batch of migrations:**
    ```sh
    php cli.php migrate rollback
    ```

-   **Create a new migration file:**
    ```sh
    php cli.php make:migration create_your_table_name_table
    ```
    (Replace `create_your_table_name_table` with a descriptive name for your migration.)

### Database Seeders

-   **Run all database seeders:**
    ```sh
    php cli.php seed
    ```

-   **Create a new seeder file:**
    ```sh
    php cli.php make:seeder YourSeederName
    ```
    (Replace `YourSeederName` with a descriptive name for your seeder class.)

## Composer Scripts

You can use the following Composer scripts for common development tasks:

- **Start the development server:**
    ```sh
    composer serve
    ```
- **Run tests:**
    ```sh
    composer test
    ```
- **Run code linter:**
    ```sh
    composer lint
    ```
- **Run security audit:**
    ```sh
    composer security-audit
    ```
- **Run database migrations:**
    ```sh
    composer migrate
    ```

## Troubleshooting

- If `composer lint` returns error code 1, it means code style violations were found. Review the output for details and fix the reported issues.
- If you see "The system cannot find the path specified," make sure all directories in the lint script exist. The script has been updated to only include existing directories.
- The `audit` script was renamed to `security-audit` to avoid conflict with Composer's built-in command.

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines on:
- Code style and standards
- Pull request process
- Testing requirements
- Migration creation guidelines

## License

See [LICENSE](LICENSE) for details.

## Support

For issues and questions:
- Check the [API Documentation](http://localhost:8000/api/v1/docs)
- Review [DATABASE.md](DATABASE.md) for schema details
- Contact the development team

---

**Version**: 1.0.0  
**Last Updated**: December 2025