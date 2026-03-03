# Staff Management API Documentation

## Overview
Complete API documentation for staff management including registration, updates, status management, and deletion with cascade relationships.

## Database Relationships
All staff-related tables have foreign key constraints with CASCADE DELETE:
- `staff_address` → `staff`
- `staff_academic_history` → `staff`
- `staff_appointment_history` → `staff`
- `staff_class` → `staff`
- `staff_subjects` → `staff`
- `staff_roles` → `staff`
- `users` → `staff` (optional)

**Important**: When a staff record is permanently deleted, ALL related records are automatically deleted.

## Setup

### Run Migrations
```bash
php kiro migrate
```

This will create:
1. `notification_l