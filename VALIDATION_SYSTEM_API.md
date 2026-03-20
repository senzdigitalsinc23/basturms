# AGH Validation System API Documentation

## Overview
Complete API documentation for the AGH Validation System with comprehensive staff management.

## Base URL
```
http://localhost:8000/api/v1
```

## Authentication
All endpoints (except login) require:
- **API Key**: `devKey123` in `X-API-Key` header
- **JWT Token**: Bearer token in `Authorization` header

## API Endpoints

### 1. Authentication

#### Login
```http
POST /validation/auth/login
Content-Type: application/json

{
  "email": "admin@agh.edu.gh",
  "password": "password123"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "user": {
    "id": 1,
    "name": "Admin User",
    "email": "admin@agh.edu.gh",
    "role": "admin",
    "unitId": 1,
    "unitName": "Administration"
  }
}
```

#### Get Current User
```http
GET /validation/auth/me
Authorization: Bearer {token}
X-API-Key: devKey123
```

---

### 2. Units Management

#### Get All Units
```http
GET /validation/units
Authorization: Bearer {token}
X-API-Key: devKey123
```

**Response:**
```json
{
  "success": true,
  "units": [
    {
      "id": 1,
      "name": "Administration",
      "description": "Administrative unit"
    }
  ]
}
```

#### Create Unit
```http
POST /validation/units
Authorization: Bearer {token}
X-API-Key: devKey123
Content-Type: application/json

{
  "name": "Finance Department",
  "description": "Handles financial operations"
}
```

---

### 3. Staff Management (Simple)

#### Get All Staff
```http
GET /validation/staff
Authorization: Bearer {token}
X-API-Key: devKey123
```

**Response:**
```json
{
  "success": true,
  "staff": [
    {
      "id": 1,
      "name": "John Doe",
      "email": "john@agh.edu.gh",
      "role": "staff",
      "unitId": 1,
      "unitName": "Administration"
    }
  ]
}
```

#### Create Staff (Simple)
```http
POST /validation/staff
Authorization: Bearer {token}
X-API-Key: devKey123
Content-Type: application/json

{
  "name": "Jane Smith",
  "email": "jane@agh.edu.gh",
  "password": "password123",
  "unitId": 1,
  "role": "staff"
}
```

---

### 4. Comprehensive Staff Management

#### Create Comprehensive Staff Record
```http
POST /staff/comprehensive/create
Authorization: Bearer {token}
X-API-Key: devKey123
Content-Type: application/json

{
  "email": "newstaff@agh.edu.gh",
  "password": "password123",
  "role": "staff",
  "personal_info": {
    "title": "Mr",
    "first_name": "Kwame",
    "middle_name": "Kofi",
    "last_name": "Mensah",
    "date_of_birth": "1990-05-15",
    "gender": "Male",
    "marital_status": "Married",
    "nationality": "Ghanaian",
    "national_id_type": "Ghana Card",
    "national_id_number": "GHA-123456789-0",
    "ssnit_number": "C123456789",
    "tin_number": "TIN123456"
  },
  "contact_info": {
    "primary_phone": "0244123456",
    "secondary_phone": "0201234567",
    "personal_email": "kwame@gmail.com",
    "work_email": "kwame@agh.edu.gh",
    "residential_address": "123 Main Street, Accra",
    "residential_city": "Accra",
    "residential_region": "Greater Accra",
    "residential_gps_address": "GA-123-4567",
    "hometown": "Kumasi",
    "home_region": "Ashanti"
  },
  "employment_info": {
    "employee_number": "EMP2026001",
    "staff_category": "Senior Staff",
    "employment_type": "Permanent",
    "employment_status": "Active",
    "date_of_first_appointment": "2026-01-01",
    "unit_id": 1,
    "position_title": "Senior Lecturer",
    "job_grade": "SL1",
    "salary_grade": "14",
    "step_level": 1
  },
  "emergency_contacts": [
    {
      "contact_name": "Ama Mensah",
      "relationship": "Spouse",
      "phone_number": "0244987654",
      "is_primary": true
    }
  ],
  "qualifications": [
    {
      "qualification_type": "PhD",
      "institution_name": "University of Ghana",
      "qualification_name": "Doctor of Philosophy",
      "field_of_study": "Computer Science",
      "completion_date": "2020-12-15",
      "is_highest_qualification": true
    }
  ],
  "bank_info": {
    "bank_name": "GCB Bank",
    "branch_name": "Accra Main",
    "account_number": "1234567890",
    "account_name": "Kwame Kofi Mensah",
    "account_type": "Savings",
    "is_primary": true
  },
  "dependents": [
    {
      "full_name": "Kofi Mensah Jr",
      "relationship": "Child",
      "date_of_birth": "2015-03-20",
      "gender": "Male",
      "is_beneficiary": true
    }
  ]
}
```

**Response:**
```json
{
  "success": true,
  "message": "Staff created successfully",
  "staff_id": 5
}
```

#### Get Comprehensive Staff Details
```http
GET /staff/comprehensive/{id}
Authorization: Bearer {token}
X-API-Key: devKey123
```

**Response:**
```json
{
  "success": true,
  "staff": {
    "id": 5,
    "name": "Kwame Kofi Mensah",
    "email": "newstaff@agh.edu.gh",
    "role": "staff",
    "personal_info": { ... },
    "contact_info": { ... },
    "employment_info": { ... },
    "emergency_contacts": [ ... ],
    "qualifications": [ ... ],
    "bank_info": [ ... ],
    "dependents": [ ... ]
  }
}
```

---

### 5. Validation Management

#### Validate Staff
```http
POST /validations
Authorization: Bearer {token}
X-API-Key: devKey123
Content-Type: application/json

{
  "staffIds": [1, 2, 3],
  "month": "March",
  "year": 2026
}
```

**Response:**
```json
{
  "success": true,
  "message": "Staff validated successfully"
}
```

#### Get Validations
```http
GET /validations?month=March&year=2026
Authorization: Bearer {token}
X-API-Key: devKey123
```

**Response:**
```json
{
  "success": true,
  "validations": [
    {
      "id": 1,
      "staffId": 1,
      "month": "March",
      "year": 2026,
      "validated": true,
      "validatedBy": 2,
      "validatedAt": "2026-03-11 10:30:00",
      "staffName": "John Doe",
      "validatedByName": "Admin User"
    }
  ]
}
```

---

## Database Schema

### Core Tables
1. **units** - Organizational units
2. **departments** - Departments within the organization
3. **validation_staff** - Main staff table
4. **validations** - Validation records

### Comprehensive Staff Tables
5. **staff_personal_info** - Personal details
6. **staff_contact_info** - Contact information
7. **staff_emergency_contacts** - Emergency contacts
8. **staff_employment_info** - Employment details
9. **staff_qualifications** - Educational qualifications
10. **staff_certifications** - Professional certifications
11. **staff_work_experience** - Previous work experience
12. **staff_bank_info** - Banking information
13. **staff_dependents** - Dependents/beneficiaries
14. **staff_documents** - Document uploads
15. **staff_leave_records** - Leave management
16. **staff_performance_reviews** - Performance reviews
17. **staff_training_records** - Training history
18. **staff_disciplinary_records** - Disciplinary actions

All tables use **INT AUTO_INCREMENT** for primary keys.

---

## Role-Based Access

### Admin
- Full access to all endpoints
- Can view and manage all staff
- Can validate any staff member

### Accountant/HR
- Full access to all endpoints
- Can view and manage all staff
- Can validate any staff member

### Incharge
- Can view staff in their unit only
- Can validate staff in their unit
- Limited management capabilities

### Staff
- Can view their own information
- Cannot validate others
- Read-only access

---

## Error Responses

### 400 Bad Request
```json
{
  "success": false,
  "message": "Missing required fields"
}
```

### 401 Unauthorized
```json
{
  "success": false,
  "message": "Invalid email or password"
}
```

### 404 Not Found
```json
{
  "success": false,
  "message": "Staff not found"
}
```

### 500 Internal Server Error
```json
{
  "success": false,
  "message": "Failed to create staff: [error details]"
}
```

---

## Testing with cURL

### Login Example
```bash
curl -X POST http://localhost:8000/api/v1/validation/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@agh.edu.gh",
    "password": "password123"
  }'
```

### Get Staff Example
```bash
curl -X GET http://localhost:8000/api/v1/validation/staff \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "X-API-Key: devKey123"
```

### Validate Staff Example
```bash
curl -X POST http://localhost:8000/api/v1/validations \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "X-API-Key: devKey123" \
  -H "Content-Type: application/json" \
  -d '{
    "staffIds": [1, 2, 3],
    "month": "March",
    "year": 2026
  }'
```

---

## Notes

- All endpoints use JWT authentication (except login)
- CORS is enabled for `http://localhost:3000`
- CSRF protection is disabled for validation API endpoints
- Database is fully normalized (3NF)
- All IDs are integers with auto-increment
- Transactions ensure data integrity for complex operations
