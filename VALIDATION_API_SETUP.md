# Validation System API Setup Guide

## Overview
This API provides backend services for the AGH Validation UI system, enabling staff validation management across different organizational units.

## Database Setup

### 1. Run Migrations
```bash
php bin/console migrate
```

This will create the following tables:
- `units` - Organizational units/departments
- `validation_staff` - Staff members with roles (admin, accountant, incharge, staff)
- `validations` - Validation records for staff by month/year

### 2. Seed Initial Data
```bash
php bin/console seed ValidationSeeder
```

This creates:
- 4 sample units (HR, Finance, IT, Operations)
- 1 Admin user
- 1 Accountant user
- 4 Incharge users (one per unit)
- 12 Staff members (3 per unit)

## Default Credentials

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@validation.com | admin123 |
| Accountant | accountant@validation.com | accountant123 |
| Incharge | incharge1@validation.com | incharge123 |
| Staff | humanresources.staff1@validation.com | staff123 |

## API Endpoints

### Authentication
- `POST /api/v1/validation/auth/login` - Login and get JWT token
- `GET /api/v1/validation/auth/me` - Get current user info

### Units Management
- `GET /api/v1/validation/units` - Get all units
- `POST /api/v1/validation/units` - Create new unit (Admin only)

### Staff Management
- `GET /api/v1/validation/staff` - Get all staff (filtered by role)
- `POST /api/v1/validation/staff` - Create new staff member (Admin only)

### Validations
- `POST /api/v1/validations` - Validate staff members
- `GET /api/v1/validations?month=January&year=2026` - Get validations for month/year

## Environment Configuration

Update your `.env` file:

```env
# Database Configuration
DB_HOST=127.0.0.1
DB_NAME=validation_db
DB_USER=root
DB_PASS=your_password
DB_DRIVER=mysql

# JWT Secret (generate using: php -r "echo bin2hex(random_bytes(32));")
JWT_SECRET=your-generated-secret-key

# CORS Configuration (allow your frontend)
CORS_ALLOWED_ORIGINS="http://localhost:3000"

# API Configuration
API_KEY=your-api-key-change-this
API_SECRET=your-api-secret-change-this
```

## Frontend Integration

Update your Next.js frontend API URLs to point to this backend:

```typescript
// In agh-validation-ui/app/api/auth/login/route.ts
const API_URL = 'http://localhost:8000/api/v1';

// Example API call
const response = await fetch(`${API_URL}/validation/auth/login`, {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({ email, password }),
});
```

## Role-Based Access Control

### Admin
- Can view all staff across all units
- Can create new staff and units
- Can validate any staff member

### Accountant (HR)
- Can view all staff across all units
- Can validate any staff member
- Cannot create staff or units

### Incharge
- Can only view staff in their own unit
- Can validate staff in their own unit
- Cannot create staff or units

### Staff
- Can only view their own information
- Cannot validate others

## API Request Examples

### Login
```bash
curl -X POST http://localhost:8000/api/v1/validation/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@validation.com","password":"admin123"}'
```

### Get Staff (with JWT token)
```bash
curl -X GET http://localhost:8000/api/v1/validation/staff \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "X-API-Key: your-api-key"
```

### Validate Staff
```bash
curl -X POST http://localhost:8000/api/v1/validations \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "X-API-Key: your-api-key" \
  -H "Content-Type: application/json" \
  -d '{
    "staffIds": ["staff-uuid-1", "staff-uuid-2"],
    "month": "January",
    "year": 2026
  }'
```

## Testing

Start the development server:
```bash
php bin/console serve
```

The API will be available at `http://localhost:8000`

## Security Notes

1. Change all default passwords in production
2. Generate a strong JWT_SECRET before deployment
3. Update API_KEY and API_SECRET in .env
4. Configure CORS_ALLOWED_ORIGINS to only allow your frontend domain
5. Enable HTTPS in production
6. Set APP_ENV=production and APP_DEBUG=false in production

## Troubleshooting

### Database Connection Issues
- Verify DB credentials in .env
- Ensure MySQL/MariaDB is running
- Check database exists: `CREATE DATABASE validation_db;`

### CORS Errors
- Update CORS_ALLOWED_ORIGINS in .env
- Ensure frontend URL matches exactly (including port)

### JWT Token Issues
- Verify JWT_SECRET is set in .env
- Check token expiration (24 hours by default)
- Ensure Authorization header format: `Bearer <token>`

## Next Steps

1. Run migrations: `php bin/console migrate`
2. Seed data: `php bin/console seed ValidationSeeder`
3. Start server: `php bin/console serve`
4. Update frontend API URLs
5. Test login and API endpoints
6. Change default passwords
