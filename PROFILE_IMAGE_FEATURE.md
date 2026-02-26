# Profile Image Feature

**Date:** February 22, 2026  
**Status:** ✅ COMPLETE  
**Version:** 1.0.0

---

## Overview

Enhanced the user profile feature to include profile image support. Users can now upload, update, and remove their profile images. Profile image details are automatically included when fetching user profiles.

---

## What's New

### ✅ Database Changes
- Added `profile_image_id` column to `users` table
- Foreign key relationship with `uploads` table
- Indexed for performance

### ✅ Profile Image Management
- **Upload Profile Image:** Link uploaded image to user profile
- **Update Profile Image:** Change existing profile image
- **Remove Profile Image:** Remove profile image from profile
- **Fetch Profile Image:** Automatically included in profile data

### ✅ Profile Data Enhancement
- Profile image details included in all profile responses
- Complete image metadata (URL, type, size, upload date)
- Null-safe handling for users without profile images

---

## Database Migration

### Migration File
`Database/migrations/20260222120000_add_profile_image_to_users.php`

### Changes
```sql
-- Add profile_image_id column
ALTER TABLE users 
ADD COLUMN profile_image_id INT NULL AFTER email;

-- Add foreign key to uploads table
ALTER TABLE users 
ADD CONSTRAINT fk_users_profile_image 
FOREIGN KEY (profile_image_id) 
REFERENCES uploads(id) 
ON DELETE SET NULL 
ON UPDATE CASCADE;

-- Add index for performance
ALTER TABLE users 
ADD INDEX idx_profile_image_id (profile_image_id);
```

### Running the Migration
```bash
php bin/console migrate
```

---

## API Endpoints

### 1. Get Profile (with Image)
```http
GET /api/v1/profile/details
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "message": "Profile retrieved successfully",
  "data": {
    "id": 1,
    "user_id": "USR001",
    "username": "john_doe",
    "email": "john@example.com",
    "profile_image_id": 123,
    "profile_image": {
      "id": 123,
      "name": "profile_pic.jpg",
      "url": "/uploads/profile_pictures/profile_pic.jpg",
      "type": "image/jpeg",
      "size": 245678,
      "uploaded_at": "2026-02-22 10:30:00"
    },
    "role_id": 2,
    "role_name": "Teacher",
    "status": "active",
    "is_super_admin": false,
    "created_at": "2026-01-15 10:30:00",
    "updated_at": "2026-02-22 15:45:00"
  }
}
```

**Response (No Profile Image):**
```json
{
  "success": true,
  "message": "Profile retrieved successfully",
  "data": {
    "id": 1,
    "user_id": "USR001",
    "username": "john_doe",
    "email": "john@example.com",
    "profile_image_id": null,
    "profile_image": null,
    "role_id": 2,
    "role_name": "Teacher",
    "status": "active",
    "is_super_admin": false,
    "created_at": "2026-01-15 10:30:00",
    "updated_at": "2026-02-22 15:45:00"
  }
}
```

### 2. Update Profile Image
```http
PUT /api/v1/profile/image
Authorization: Bearer {token}
Content-Type: application/json

{
  "upload_id": 123
}
```

**Success Response:**
```json
{
  "success": true,
  "message": "Profile image updated successfully",
  "data": {
    "id": 1,
    "user_id": "USR001",
    "username": "john_doe",
    "email": "john@example.com",
    "profile_image_id": 123,
    "profile_image": {
      "id": 123,
      "name": "new_profile_pic.jpg",
      "url": "/uploads/profile_pictures/new_profile_pic.jpg",
      "type": "image/jpeg",
      "size": 198765,
      "uploaded_at": "2026-02-22 16:20:00"
    },
    "role_id": 2,
    "role_name": "Teacher",
    "status": "active",
    "is_super_admin": false,
    "created_at": "2026-01-15 10:30:00",
    "updated_at": "2026-02-22 16:20:00"
  }
}
```

**Error Response:**
```json
{
  "success": false,
  "message": "Upload not found",
  "data": null
}
```

### 3. Remove Profile Image
```http
DELETE /api/v1/profile/image
Authorization: Bearer {token}
```

**Success Response:**
```json
{
  "success": true,
  "message": "Profile image removed successfully",
  "data": {
    "id": 1,
    "user_id": "USR001",
    "username": "john_doe",
    "email": "john@example.com",
    "profile_image_id": null,
    "profile_image": null,
    "role_id": 2,
    "role_name": "Teacher",
    "status": "active",
    "is_super_admin": false,
    "created_at": "2026-01-15 10:30:00",
    "updated_at": "2026-02-22 16:25:00"
  }
}
```

---

## Complete Workflow

### Step 1: Upload Image
First, upload the image file using the existing upload endpoint:

```http
POST /api/v1/uploads
Authorization: Bearer {token}
Content-Type: multipart/form-data

file: [image file]
doc_type: profile_picture
```

**Response:**
```json
{
  "success": true,
  "message": "File uploaded successfully",
  "data": {
    "id": 123,
    "doc_name": "profile_pic.jpg",
    "url": "/uploads/profile_pictures/profile_pic.jpg",
    "file_type": "image/jpeg",
    "file_size": 245678
  }
}
```

### Step 2: Set as Profile Image
Use the upload ID to set it as your profile image:

```http
PUT /api/v1/profile/image
Authorization: Bearer {token}
Content-Type: application/json

{
  "upload_id": 123
}
```

### Step 3: View Profile
Your profile now includes the image:

```http
GET /api/v1/profile/details
Authorization: Bearer {token}
```

---

## Frontend Integration

### JavaScript Example
```javascript
// Complete workflow: Upload and set profile image
async function uploadAndSetProfileImage