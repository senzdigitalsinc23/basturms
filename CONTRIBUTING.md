# Contributing to Basturms School Management System

Thank you for your interest in contributing to the Basturms School Management System! This document provides guidelines and best practices for contributing to the project.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Getting Started](#getting-started)
- [Development Workflow](#development-workflow)
- [Coding Standards](#coding-standards)
- [Database Migrations](#database-migrations)
- [Testing](#testing)
- [Pull Request Process](#pull-request-process)
- [Documentation](#documentation)

## Code of Conduct

- Be respectful and inclusive
- Provide constructive feedback
- Focus on what is best for the project
- Show empathy towards other contributors

## Getting Started

### Prerequisites

- PHP 8.2+
- MySQL 5.7+ or MariaDB 10.3+
- Composer
- Git

### Setting Up Development Environment

1. **Fork and Clone**
   ```bash
   git clone https://github.com/your-username/basturms.git
   cd basturms
   ```

2. **Install Dependencies**
   ```bash
   composer install
   ```

3. **Configure Environment**
   ```bash
   cp .sample.env .env
   # Edit .env with your local settings
   ```

4. **Run Migrations**
   ```bash
   php cli.php migrate
   ```

5. **Start Development Server**
   ```bash
   composer serve
   ```

## Development Workflow

### Branching Strategy

- `main` - Production-ready code
- `develop` - Development branch
- `feature/*` - New features
- `bugfix/*` - Bug fixes
- `hotfix/*` - Urgent production fixes

### Creating a Feature Branch

```bash
git checkout develop
git pull origin develop
git checkout -b feature/your-feature-name
```

### Committing Changes

Use clear, descriptive commit messages:

```bash
git commit -m "Add student promotion validation logic"
```

**Commit Message Format**:
```
<type>: <subject>

<body>

<footer>
```

**Types**:
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Code style changes (formatting, etc.)
- `refactor`: Code refactoring
- `test`: Adding or updating tests
- `chore`: Maintenance tasks

**Example**:
```
feat: Add account lockout after failed login attempts

Implement automatic account lockout policy that locks user
accounts for 30 minutes after 5 consecutive failed login
attempts. Administrators can manually unlock accounts.

Closes #123
```

## Coding Standards

### PHP Standards

Follow PSR-12 coding standards:

```php
<?php

namespace App\Services;

use App\DTOs\UserDTO;
use App\Repositories\UserRepository;

class UserService
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function getUserById(int $id): ?UserDTO
    {
        return $this->userRepository->findById($id);
    }
}
```

### Code Style Guidelines

- **Indentation**: 4 spaces (no tabs)
- **Line Length**: Maximum 120 characters
- **Braces**: Opening brace on same line
- **Naming**:
  - Classes: `PascalCase`
  - Methods: `camelCase`
  - Constants: `UPPER_SNAKE_CASE`
  - Variables: `camelCase`

### Running Code Linter

```bash
composer lint
```

Fix linting issues before committing.

### Type Hints

Always use type hints for parameters and return types:

```php
public function calculateAverage(array $scores): float
{
    return array_sum($scores) / count($scores);
}
```

### Documentation

Add PHPDoc blocks for all classes and methods:

```php
/**
 * Calculate the average of an array of scores.
 *
 * @param array $scores Array of numeric scores
 * @return float The calculated average
 * @throws InvalidArgumentException If scores array is empty
 */
public function calculateAverage(array $scores): float
{
    if (empty($scores)) {
        throw new InvalidArgumentException('Scores array cannot be empty');
    }
    
    return array_sum($scores) / count($scores);
}
```

## Database Migrations

### Creating a Migration

```bash
php cli.php make:migration create_your_table_name
```

### Migration Structure

```php
<?php

use Database\Migration;

class CreateYourTableName20251226000000 extends Migration
{
    public function up(): void
    {
        $this->execute("
            CREATE TABLE your_table (
                id INT(11) AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS your_table");
    }
}
```

### Migration Guidelines

1. **Always provide a `down()` method** for rollback capability
2. **Use transactions** for complex migrations
3. **Test migrations** in development before committing
4. **Document schema changes** in DATABASE.md
5. **Never modify existing migrations** - create new ones instead

### Adding Foreign Keys

```php
public function up(): void
{
    $this->execute("
        ALTER TABLE students 
        ADD CONSTRAINT fk_students_class 
        FOREIGN KEY (class_id) 
        REFERENCES classes(class_id) 
        ON DELETE RESTRICT 
        ON UPDATE CASCADE
    ");
}
```

## Testing

### Running Tests

```bash
composer test
```

### Writing Tests

Create test files in the `tests/` directory:

```php
<?php

use PHPUnit\Framework\TestCase;
use App\Services\UserService;

class UserServiceTest extends TestCase
{
    public function testGetUserById(): void
    {
        $userService = new UserService();
        $user = $userService->getUserById(1);
        
        $this->assertNotNull($user);
        $this->assertEquals(1, $user->id);
    }
}
```

### Test Coverage

- Aim for at least 80% code coverage
- Test all public methods
- Include edge cases and error scenarios
- Mock external dependencies

## Pull Request Process

### Before Submitting

1. **Update from develop**
   ```bash
   git checkout develop
   git pull origin develop
   git checkout your-feature-branch
   git rebase develop
   ```

2. **Run tests and linter**
   ```bash
   composer test
   composer lint
   ```

3. **Update documentation** if needed

### Creating a Pull Request

1. Push your branch to GitHub
2. Create a Pull Request to `develop` branch
3. Fill out the PR template completely
4. Link related issues

### PR Title Format

```
[Type] Brief description

Examples:
[Feature] Add student promotion validation
[Bugfix] Fix account lockout counter issue
[Docs] Update API documentation
```

### PR Description Template

```markdown
## Description
Brief description of changes

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update

## Testing
Describe testing performed

## Checklist
- [ ] Code follows project style guidelines
- [ ] Self-review completed
- [ ] Comments added for complex code
- [ ] Documentation updated
- [ ] Tests added/updated
- [ ] All tests passing
- [ ] No new warnings
```

### Code Review Process

- At least one approval required
- Address all review comments
- Keep PRs focused and reasonably sized
- Be responsive to feedback

## Documentation

### When to Update Documentation

Update documentation when you:
- Add new features
- Change API endpoints
- Modify database schema
- Update configuration options
- Fix significant bugs

### Documentation Files

- **README.md** - Project overview and setup
- **DATABASE.md** - Schema documentation
- **API.md** - API usage guide
- **CONTRIBUTING.md** - This file

### API Documentation

Add OpenAPI annotations to new endpoints:

```php
#[OA\Post(
    path: "/students/create",
    summary: "Create a new student",
    tags: ["Students"],
    security: [["ApiKeyAuth" => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ["first_name", "last_name"],
            properties: [
                new OA\Property(property: "first_name", type: "string"),
                new OA\Property(property: "last_name", type: "string")
            ]
        )
    ),
    responses: [
        new OA\Response(response: 201, description: "Student created"),
        new OA\Response(response: 400, description: "Validation error")
    ]
)]
public function create(Request $request, Response $response): Response
{
    // Implementation
}
```

## Security

### Reporting Security Issues

**DO NOT** create public issues for security vulnerabilities.

Email security concerns to: [security contact email]

### Security Guidelines

- Never commit sensitive data (`.env`, API keys, passwords)
- Always use parameterized queries
- Validate and sanitize all user input
- Use prepared statements for database queries
- Implement proper authentication and authorization
- Keep dependencies up to date

## Questions?

If you have questions about contributing:
- Check existing documentation
- Review closed issues and PRs
- Ask in the project discussions
- Contact the maintainers

---

Thank you for contributing to Basturms School Management System!
