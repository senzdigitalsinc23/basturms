# API Documentation

## Overview

The Basturms School Management System provides a RESTful API with comprehensive OpenAPI/Swagger documentation. All API endpoints follow RESTful conventions and return JSON responses.

**Base URL**: `http://localhost:8000/api/v1`

## Interactive Documentation

Access the interactive Swagger UI for testing and exploring the API:

- **Swagger UI**: [http://localhost:8000/api/v1/docs](http://localhost:8000/api/v1/docs)
- **OpenAPI JSON**: [http://localhost:8000/api/v1/swagger](http://localhost:8000/api/v1/swagger)

## Authentication

The API supports two authentication methods that can be used independently or together.

### 1. API Key Authentication

Include the API key in the request header:

```http
X-API-Key: your-api-key-here
```

**Example**:
```bash
curl -H "X-API-Key: your-api-key" \
  http://localhost:8000/api/v1/students
```

### 2. JWT Bearer Token

Include the JWT token in the Authorization header:

```http
Authorization: Bearer your-jwt-token-here
```

**Getting a Token**:
```bash
curl -X POST http://localhost:8000/api/v1/login \
  -H "Content-Type: application/json" \
  -H "X-API-Key: your-api-key" \
  -d '{
    "email": "user@example.com",
    "password": "password123"
  }'
```

**Response**:
```json
{
  "success": true,
  "message": "Login successful",
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "user": {
    "id": 1,
    "email": "user@example.com",
    "role_id": 1
  }
}
```

## Rate Limiting

The API implements rate limiting to prevent abuse:

- **Default Limit**: 100 requests per minute
- **Sensitive Endpoints**: 5 requests per minute (login, registration, deletions)

**Rate Limit Headers**:
```http
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 1640000000
```

**Rate Limit Exceeded Response**:
```json
{
  "error": "Too many requests. Please try again later."
}
```
**HTTP Status**: 429 Too Many Requests

## Account Lockout Policy

Failed login attempts trigger automatic account lockout:

- **Threshold**: 5 failed attempts
- **Lockout Duration**: 30 minutes
- **Admin Unlock**: Available via admin endpoint

**Lockout Response**:
```json
{
  "success": false,
  "message": "Account is temporarily locked due to multiple failed login attempts. Please try again in 25 minutes or contact System Administrator for help."
}
```
**HTTP Status**: 423 Locked

## API Endpoints

### Authentication

#### POST /login
Authenticate a user and receive a JWT token.

**Request**:
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

**Response** (200 OK):
```json
{
  "success": true,
  "message": "Login successful",
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "user": { ... }
}
```

#### POST /register
Register a new user account.

**Request**:
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "securepassword",
  "roleId": 2
}
```

#### GET /logout
Logout the current user and invalidate the token.

#### GET /me
Get the currently authenticated user's information.

### Student Management

#### GET /students
List all students with optional filtering.

**Query Parameters**:
- `class_id` - Filter by class
- `status` - Filter by status (active, graduated, etc.)

#### POST /students/show
Get detailed information about a specific student.

**Request**:
```json
{
  "student_no": "STU001"
}
```

#### POST /students/create
Create a new student record.

#### POST /students/update
Update an existing student record.

#### POST /students/delete
Delete a student record (soft delete).

### Academic Management

#### POST /academic/years/create
Create a new academic year.

**Request**:
```json
{
  "academic_year": "2025/2026",
  "number_of_terms": 3,
  "status": "Upcoming"
}
```

#### GET /academic/years/list
List all academic years.

#### GET /academic/years/active
Get the currently active academic year.

#### POST /academic/subjects/create
Create a new subject.

**Request**:
```json
{
  "subject_name": "Mathematics",
  "subject_code": "MATH101",
  "level": "Primary",
  "category": "Core"
}
```

#### GET /academic/subjects/list
List all subjects.

#### POST /academic/classes/create
Create a new class.

#### GET /academic/classes/list
List all classes.

#### POST /academic/scores/add
Add or update a student's score.

**Request**:
```json
{
  "student_no": "STU001",
  "subject_id": 1,
  "activity_id": 1,
  "score": 85.5
}
```

#### POST /academic/activities/create
Create a new assignment activity.

**Request**:
```json
{
  "activity_name": "Mid-Term Exam",
  "max_score": 100,
  "academic_id": 1,
  "term_id": 1
}
```

#### GET /academic/activities/list
List all assignment activities.

#### POST /academic/grading-scheme/create
Create a new grading scheme entry.

**Request**:
```json
{
  "grade": "A",
  "min_score": 80,
  "max_score": 100,
  "remarks": "Excellent"
}
```

### Administration

#### GET /admin/users
List all users in the system.

#### POST /admin/users/unlock
Unlock a locked user account (admin only).

**Request**:
```json
{
  "email": "user@example.com"
}
```

## Response Format

All API responses follow a consistent format:

### Success Response
```json
{
  "success": true,
  "message": "Operation completed successfully",
  "data": { ... }
}
```

### Error Response
```json
{
  "success": false,
  "message": "Error description",
  "error": "Detailed error information",
  "data": null
}
```

## HTTP Status Codes

| Code | Description |
|------|-------------|
| 200 | OK - Request successful |
| 201 | Created - Resource created successfully |
| 400 | Bad Request - Invalid input |
| 401 | Unauthorized - Authentication required |
| 403 | Forbidden - Insufficient permissions |
| 404 | Not Found - Resource not found |
| 409 | Conflict - Resource already exists |
| 422 | Unprocessable Entity - Validation failed |
| 423 | Locked - Account locked |
| 429 | Too Many Requests - Rate limit exceeded |
| 500 | Internal Server Error - Server error |

## Error Handling

### Validation Errors (422)
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required"],
    "password": ["The password must be at least 8 characters"]
  }
}
```

### Authentication Errors (401)
```json
{
  "success": false,
  "message": "Invalid credentials"
}
```

### Account Lockout (423)
```json
{
  "success": false,
  "message": "Account is temporarily locked due to multiple failed login attempts. Please try again in 25 minutes or contact System Administrator for help."
}
```

## Security Best Practices

### For API Consumers

1. **Always use HTTPS** in production
2. **Store API keys securely** - Never commit to version control
3. **Rotate tokens regularly** - Implement token refresh
4. **Handle rate limits gracefully** - Implement exponential backoff
5. **Validate responses** - Check success status before processing data
6. **Log errors** - Monitor for unusual patterns

### Request Headers

Always include these headers:

```http
Content-Type: application/json
X-API-Key: your-api-key
Authorization: Bearer your-jwt-token
```

## Testing the API

### Using cURL

```bash
# Login
curl -X POST http://localhost:8000/api/v1/login \
  -H "Content-Type: application/json" \
  -H "X-API-Key: your-api-key" \
  -d '{"email":"user@example.com","password":"password"}'

# Get students
curl -X GET http://localhost:8000/api/v1/students \
  -H "X-API-Key: your-api-key" \
  -H "Authorization: Bearer your-jwt-token"
```

### Using Postman

1. Import the OpenAPI JSON from `/api/v1/swagger`
2. Set up environment variables for API key and token
3. Use the pre-configured requests

### Using Swagger UI

1. Navigate to [http://localhost:8000/api/v1/docs](http://localhost:8000/api/v1/docs)
2. Click "Authorize" and enter your API key
3. Test endpoints directly in the browser

## Versioning

The API uses URL-based versioning:

- **Current Version**: v1
- **Base Path**: `/api/v1`

Future versions will be available at `/api/v2`, etc., maintaining backward compatibility.

## Support

For API issues or questions:
- Check the [Swagger Documentation](http://localhost:8000/api/v1/docs)
- Review [DATABASE.md](DATABASE.md) for data structures
- Contact the development team

---

**API Version**: 1.0.0  
**Last Updated**: December 2025
