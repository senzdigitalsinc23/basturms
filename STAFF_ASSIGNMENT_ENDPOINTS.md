# Staff Class/Subject Assignment Endpoints

## Overview
These endpoints allow you to assign classes and subjects to staff members, view their assignments, and remove assignments.

## Endpoints

### 1. Assign Classes to Staff
**POST** `/api/v1/staff/{id}/assign-classes`

Assign one or more classes to a staff member.

**Request Body:**
```json
{
  "class_ids": ["jhs1", "jhs2", "jhs3"]
}
```

**Response:**
```json
{
  "success": true,
  "message": "Classes assigned successfully",
  "data": {
    "assigned": ["jhs1", "jhs2", "jhs3"],
    "already_assigned": [],
    "total_assigned": 3
  }
}
```

### 2. Assign Subjects to Staff
**POST** `/api/v1/staff/{id}/assign-subjects`

Assign subjects to a staff member for specific classes. Supports two formats for flexibility.

**Format 1: Detailed (one subject-class pair per item)**
```json
{
  "assignments": [
    {
      "subject_id": "INT-SCI",
      "class_id": "jhs1"
    },
    {
      "subject_id": "INT-SCI",
      "class_id": "jhs2"
    },
    {
      "subject_id": "MATH",
      "class_id": "jhs1"
    }
  ]
}
```

**Format 2: Simplified (one subject to multiple classes)**
```json
{
  "assignments": [
    {
      "subject_id": "INT-SCI",
      "class_ids": ["jhs1", "jhs2", "jhs3"]
    },
    {
      "subject_id": "MATH",
      "class_ids": ["jhs1", "jhs2"]
    }
  ]
}
```

**Mixed Format (both formats in one request)**
```json
{
  "assignments": [
    {
      "subject_id": "INT-SCI",
      "class_ids": ["jhs1", "jhs2", "jhs3"]
    },
    {
      "subject_id": "MATH",
      "class_id": "jhs1"
    }
  ]
}
```

**Response:**
```json
{
  "success": true,
  "message": "Subjects assigned successfully",
  "data": {
    "assigned": [
      {
        "subject_id": "INT-SCI",
        "class_id": "jhs1"
      },
      {
        "subject_id": "INT-SCI",
        "class_id": "jhs2"
      }
    ],
    "already_assigned": ["MATH for jhs1"],
    "total_assigned": 2
  }
}
```

### 3. Get Staff Assignments
**GET** `/api/v1/staff/{id}/assignments`

Retrieve all class and subject assignments for a staff member.

**Response:**
```json
{
  "success": true,
  "message": "Staff assignments retrieved successfully",
  "data": {
    "staff_id": "LBAST26001",
    "staff_name": "Joseph Konnie",
    "classes": [
      {
        "id": 1,
        "class_id": "jhs1",
        "class_name": "JHS 1",
        "assigned_by": "admin",
        "assigned_by_name": "Admin User"
      },
      {
        "id": 2,
        "class_id": "jhs2",
        "class_name": "JHS 2",
        "assigned_by": "admin",
        "assigned_by_name": "Admin User"
      }
    ],
    "subjects_by_class": [
      {
        "class_id": "jhs1",
        "class_name": "JHS 1",
        "subjects": [
          {
            "subject_id": "INT-SCI",
            "subject_name": "Integrated Science",
            "assigned_by": "Admin User",
            "assigned_on": "2026-02-28 10:30:00"
          },
          {
            "subject_id": "MATH",
            "subject_name": "Mathematics",
            "assigned_by": "Admin User",
            "assigned_on": "2026-02-28 10:30:00"
          }
        ]
      },
      {
        "class_id": "jhs2",
        "class_name": "JHS 2",
        "subjects": [
          {
            "subject_id": "INT-SCI",
            "subject_name": "Integrated Science",
            "assigned_by": "Admin User",
            "assigned_on": "2026-02-28 10:30:00"
          }
        ]
      }
    ],
    "total_classes": 2,
    "total_subjects": 3
  }
}
```

### 4. Remove Class Assignment
**DELETE** `/api/v1/staff/{id}/remove-class/{class_id}`

Remove a class assignment from a staff member. This will also automatically remove all subject assignments for this class.

**Response:**
```json
{
  "success": true,
  "message": "Class assignment removed successfully (including all related subject assignments)"
}
```

**Note:** When you remove a class assignment, all subjects the staff teaches for that class are automatically removed as well.

### 5. Remove Subject Assignment
**DELETE** `/api/v1/staff/{id}/remove-subject/{subject_id}/{class_id}`

Remove a subject assignment from a staff member for a specific class.

**Response:**
```json
{
  "success": true,
  "message": "Subject assignment removed successfully"
}
```

## Features

- **Flexible Input Format**: Subject assignment supports two formats - detailed (one subject-class pair) or simplified (one subject to multiple classes)
- **Duplicate Prevention**: The system automatically checks for existing assignments and prevents duplicates
- **Transaction Safety**: All assignment operations are wrapped in database transactions
- **Cascade Delete**: When a staff member is deleted, all their assignments are automatically removed
- **Cascade Unassignment**: When a class is unassigned from a staff member, all subject assignments for that class are automatically removed
- **Validation**: Staff existence is validated before any assignment operation
- **Detailed Response**: Assignment responses include both successful and already-assigned items

## Database Tables

### staff_class
- `id`: Auto-increment primary key
- `staff_id`: Foreign key to staff table
- `classes_assigned`: Class ID
- `assigned_by`: User who made the assignment

### staff_subjects
- `id`: Auto-increment primary key
- `staff_id`: Foreign key to staff table
- `subject_id`: Subject ID
- `class_id`: Class ID
- `assigned_by`: User who made the assignment
- `assigned_on`: Timestamp of assignment

## Usage Example

```bash
# 1. Assign classes to a teacher
curl -X POST http://localhost/api/v1/staff/LBAST26001/assign-classes \
  -H "Content-Type: application/json" \
  -H "X-API-Key: your-api-key" \
  -d '{
    "class_ids": ["jhs1", "jhs2", "jhs3"]
  }'

# 2. Assign subjects to the teacher (Format 2: Simplified - one subject to multiple classes)
curl -X POST http://localhost/api/v1/staff/LBAST26001/assign-subjects \
  -H "Content-Type: application/json" \
  -H "X-API-Key: your-api-key" \
  -d '{
    "assignments": [
      {"subject_id": "INT-SCI", "class_ids": ["jhs1", "jhs2", "jhs3"]},
      {"subject_id": "MATH", "class_ids": ["jhs1", "jhs2"]}
    ]
  }'

# Alternative: Format 1 (Detailed - one subject-class pair per item)
curl -X POST http://localhost/api/v1/staff/LBAST26001/assign-subjects \
  -H "Content-Type: application/json" \
  -H "X-API-Key: your-api-key" \
  -d '{
    "assignments": [
      {"subject_id": "INT-SCI", "class_id": "jhs1"},
      {"subject_id": "INT-SCI", "class_id": "jhs2"},
      {"subject_id": "INT-SCI", "class_id": "jhs3"}
    ]
  }'

# 3. View all assignments
curl -X GET http://localhost/api/v1/staff/LBAST26001/assignments \
  -H "X-API-Key: your-api-key"

# 4. Remove a class assignment
curl -X DELETE http://localhost/api/v1/staff/LBAST26001/remove-class/jhs3 \
  -H "X-API-Key: your-api-key"

# 5. Remove a subject assignment
curl -X DELETE http://localhost/api/v1/staff/LBAST26001/remove-subject/INT-SCI/jhs3 \
  -H "X-API-Key: your-api-key"
```

## Notes

- All endpoints require authentication (JWT token or API key)
- The `assigned_by` field is automatically populated from the authenticated user
- Assignments are class-specific for subjects (a teacher can teach the same subject to different classes)
- The system supports multiple teachers for the same class/subject combination
