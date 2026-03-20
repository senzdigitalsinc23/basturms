# Comprehensive Staff Management API

## Overview

This API provides complete staff management functionality with a fully normalized database schema designed for scalability and future expansion.

## Database Schema

The system uses **15 normalized tables** to store comprehensive staff information:

### Core Tables
1. **validation_staff** - Main staff authentication and basic info
2. **units** - Organizational units
3. **departments** - Organizational departments (hierarchical)

### Personal Information
4. **staff_personal_info** - Personal details (name, DOB, gender, IDs, etc.)
5. **staff_contact_info** - Contact details (phones, emails, addresses)
6. **staff_emergency_contacts** - Emergency contact persons
7. **staff_dependents** - Family dependents and beneficiaries

### Employment Information
8. **staff_employment_info** - Employment details (position, grade, dates, etc.)
9. **staff_bank_info** - Banking information for salary payments

### Professional Development
10. **staff_qualifications** - Educational qualifications
11. **staff_certifications** - Professional certifications
12. **staff_work_experience** - Previous employment history
13. **staff_training_records** - Training and development activities

### Performance & Compliance
14. **staff_performance_reviews** - Performance appraisals
15. **staff_leave_records** - Leave management
16. **staff_disciplinary_records** - Disciplinary actions
17. **staff_documents** - Document management

## API Endpoints

### 1. Create Comprehensive Staff Record

**Endpoint:** `POST /api/v1/staff/comprehensive/create`

**Authentication:** Required (JWT Token + API Key)

**Request Body:**
```json
{
  "email": "john.doe@hospital.com",
  "password": "SecurePassword123",
  "role": "staff",
  "personal_info": {
    "title": "Mr",
    "first_name": "John",
    "middle_name": "Kwame",
    "last_name": "Doe",
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
    "personal_email": "john.personal@gmail.com",
    "work_email": "john.doe@hospital.com",
    "residential_address": "House No. 123, Street Name",
    "residential_city": "Accra",
    "residential_region": "Greater Accra",
    "residential_gps_address": "GA-123-4567",
    "hometown": "Kumasi",
    "home_region": "Ashanti"
  },
  "employment_info": {
    "employee_number": "EMP2024001",
    "staff_category": "Senior Staff",
    "employment_type": "Permanent",
    "employment_status": "Active",
    "date_of_first_appointment": "2024-01-15",
    "date_of_current_appointment": "2024-01-15",
    "department_id": "dept-uuid",
    "unit_id": "unit-uuid",
    "position_title": "Senior Nurse",
    "job_grade": "Grade 10",
    "salary_grade": "SG-10",
    "step_level": 3,
    "work_location": "Main Hospital"
  },
  "emergency_contacts": [
    {
      "contact_name": "Jane Doe",
      "relationship": "Spouse",
      "phone_number": "0244987654",
      "alternative_phone": "0201987654",
      "address": "Same as residential",
      "is_primary": true
    }
  ],
  "qualifications": [
    {
      "qualification_type": "Degree",
      "institution_name": "University of Ghana",
      "qualification_name": "Bachelor of Nursing",
      "field_of_study": "Nursing",
      "grade_obtained": "Second Class Upper",
      "start_date": "2010-09-01",
      "completion_date": "2014-06-30",
      "certificate_number": "UG/NUR/2014/123",
      "is_highest_qualification": true
    }
  ],
  "bank_info": {
    "bank_name": "GCB Bank",
    "branch_name": "Accra Main",
    "account_number": "1234567890",
    "account_name": "John Kwame Doe",
    "account_type": "Savings",
    "is_primary": true
  },
  "dependents": [
    {
      "full_name": "Mary Doe",
      "relationship": "Child",
      "date_of_birth": "2015-03-20",
      "gender": "Female",
      "is_beneficiary": true
    }
  ]
}
```

**Response (201 Created):**
```json
{
  "success": true,
  "message": "Staff created successfully",
  "staff_id": "uuid-here"
}
```

### 2. Get Comprehensive Staff Details

**Endpoint:** `GET /api/v1/staff/comprehensive/{id}`

**Authentication:** Required (JWT Token + API Key)

**Response (200 OK):**
```json
{
  "success": true,
  "staff": {
    "id": "uuid",
    "name": "John Kwame Doe",
    "email": "john.doe@hospital.com",
    "role": "staff",
    "unit_name": "Nursing Department",
    "personal_info": {
      "title": "Mr",
      "first_name": "John",
      "middle_name": "Kwame",
      "last_name": "Doe",
      "date_of_birth": "1990-05-15",
      "gender": "Male",
      "marital_status": "Married",
      "nationality": "Ghanaian",
      "national_id_type": "Ghana Card",
      "national_id_number": "GHA-123456789-0",
      "ssnit_number": "C123456789"
    },
    "contact_info": {
      "primary_phone": "0244123456",
      "residential_address": "House No. 123, Street Name",
      "residential_city": "Accra"
    },
    "employment_info": {
      "employee_number": "EMP2024001",
      "staff_category": "Senior Staff",
      "employment_type": "Permanent",
      "position_title": "Senior Nurse",
      "department_name": "Medical Services",
      "unit_name": "Nursing Department"
    },
    "emergency_contacts": [...],
    "qualifications": [...],
    "bank_info": [...],
    "dependents": [...]
  }
}
```

## Field Descriptions

### Personal Information Fields

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| title | enum | No | Mr, Mrs, Miss, Dr, Prof, Rev, Hon |
| first_name | string | Yes | First name |
| middle_name | string | No | Middle name |
| last_name | string | Yes | Last name |
| maiden_name | string | No | Maiden name (if applicable) |
| date_of_birth | date | Yes | Date of birth (YYYY-MM-DD) |
| gender | enum | Yes | Male, Female, Other |
| marital_status | enum | No | Single, Married, Divorced, Widowed, Separated |
| nationality | string | No | Default: Ghanaian |
| national_id_type | enum | No | Ghana Card, Passport, Voters ID, Drivers License, SSNIT |
| national_id_number | string | No | National ID number |
| ssnit_number | string | No | SSNIT number |
| tin_number | string | No | Tax Identification Number |

### Employment Information Fields

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| employee_number | string | No | Unique employee number |
| staff_category | enum | Yes | Senior Staff, Junior Staff, Senior Member, Contract, National Service, Casual |
| employment_type | enum | Yes | Permanent, Contract, Temporary, Part-Time, Casual |
| employment_status | enum | No | Active, On Leave, Suspended, Terminated, Retired, Resigned |
| date_of_first_appointment | date | Yes | First appointment date |
| date_of_current_appointment | date | No | Current position appointment date |
| confirmation_date | date | No | Confirmation date |
| department_id | uuid | No | Department ID |
| unit_id | uuid | No | Unit ID |
| position_title | string | Yes | Job title |
| job_grade | string | No | Job grade |
| salary_grade | string | No | Salary grade |
| step_level | integer | No | Step level within grade |
| reports_to | uuid | No | Supervisor's staff ID |
| work_location | string | No | Work location |

## Database Normalization Benefits

1. **Data Integrity** - Foreign key constraints ensure referential integrity
2. **Scalability** - Easy to add new attributes without affecting existing data
3. **Flexibility** - Support for multiple contacts, qualifications, dependents, etc.
4. **Audit Trail** - Created/updated timestamps on all tables
5. **Soft Deletes** - Deleted_at columns for data retention
6. **Performance** - Proper indexing on frequently queried fields

## Future Expansion Capabilities

The schema is designed to support:
- Payroll management
- Performance management system
- Leave management system
- Training and development tracking
- Document management
- Disciplinary procedures
- Career progression tracking
- Succession planning
- Skills inventory
- Compliance tracking

## Usage Examples

### Create Staff with Minimal Information
```bash
curl -X POST http://localhost:8000/api/v1/staff/comprehensive/create \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "X-API-Key: devKey123" \
  -d '{
    "email": "staff@hospital.com",
    "password": "password123",
    "personal_info": {
      "first_name": "John",
      "last_name": "Doe",
      "date_of_birth": "1990-01-01",
      "gender": "Male"
    },
    "contact_info": {
      "primary_phone": "0244123456",
      "residential_address": "Accra"
    },
    "employment_info": {
      "staff_category": "Senior Staff",
      "employment_type": "Permanent",
      "date_of_first_appointment": "2024-01-01",
      "position_title": "Nurse",
      "unit_id": "unit-uuid"
    }
  }'
```

### Get Staff Details
```bash
curl -X GET http://localhost:8000/api/v1/staff/comprehensive/STAFF_ID \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "X-API-Key: devKey123"
```

## Security Considerations

1. **Authentication Required** - All endpoints require valid JWT token
2. **API Key Required** - Additional API key validation
3. **Role-Based Access** - Only admins can create staff
4. **Password Hashing** - Passwords are hashed using bcrypt
5. **Input Validation** - All inputs are validated before processing
6. **SQL Injection Protection** - Prepared statements used throughout
7. **Transaction Support** - Database transactions ensure data consistency

## Error Handling

All endpoints return consistent error responses:

```json
{
  "success": false,
  "message": "Error description here"
}
```

Common HTTP status codes:
- 200: Success
- 201: Created
- 400: Bad Request (validation error)
- 401: Unauthorized
- 404: Not Found
- 500: Internal Server Error
