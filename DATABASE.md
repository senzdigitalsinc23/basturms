# Database Schema Documentation

## Overview

The Basturms School Management System uses a MySQL/MariaDB database with a comprehensive schema designed to manage all aspects of school operations including academics, students, staff, finances, and administration.

**Database Charset**: UTF8MB4  
**Collation**: utf8mb4_unicode_ci

## Schema Organization

The database is organized into the following functional areas:

1. [Core System Tables](#core-system-tables)
2. [Academic Management](#academic-management)
3. [Student Management](#student-management)
4. [Staff Management](#staff-management)
5. [Financial Management](#financial-management)
6. [Security & Audit](#security--audit)
7. [Supporting Tables](#supporting-tables)
8. [Views](#database-views)

---

## Core System Tables

### `users`
Stores user accounts for system access.

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT(11) PK | Auto-increment primary key |
| `user_id` | VARCHAR(20) UNIQUE | Unique user identifier |
| `username` | VARCHAR(20) UNIQUE | Username for login |
| `email` | VARCHAR(100) UNIQUE | User email address |
| `password` | VARCHAR(255) | Hashed password |
| `role_id` | INT(11) FK | Foreign key to `roles` table |
| `status` | ENUM('active','inactive') | Account status |
| `failed_login_attempts` | INT(11) | Counter for failed login attempts |
| `locked_until` | DATETIME NULL | Lockout expiration timestamp |
| `locked_by_admin` | TINYINT(1) | Admin-initiated lock flag |
| `is_super_admin` | TINYINT(1) | Super admin flag |
| `created_at` | DATETIME | Account creation timestamp |
| `updated_at` | DATETIME | Last update timestamp |

**Indexes**:
- PRIMARY KEY (`id`)
- UNIQUE (`user_id`, `username`, `email`)
- INDEX (`role_id`, `status`, `created_at`, `locked_until`)

**Recent Changes** (2025-12-26):
- Added `failed_login_attempts`, `locked_until`, `locked_by_admin` for account lockout policy

### `roles`
Defines user roles and permissions.

| Column | Type | Description |
|--------|------|-------------|
| `role_id` | INT(11) PK | Primary key |
| `name` | VARCHAR(50) | Role name |
| `description` | TEXT | Role description |

### `permissions`
Defines system permissions.

| Column | Type | Description |
|--------|------|-------------|
| `permission_id` | INT(11) PK | Primary key |
| `name` | VARCHAR(100) | Permission name |
| `description` | TEXT | Permission description |

### `permission_role`
Maps permissions to roles (many-to-many).

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT(11) PK | Primary key |
| `role_id` | INT(11) FK | Foreign key to `roles` |
| `permission_id` | INT(11) FK | Foreign key to `permissions` |

---

## Academic Management

### `academic_years`
Stores academic year information.

| Column | Type | Description |
|--------|------|-------------|
| `academic_id` | INT(11) PK | Primary key |
| `academic_year` | VARCHAR(50) UNIQUE | Year identifier (e.g., "2024/2025") |
| `status` | ENUM | Year status (Active, Upcoming, Completed, Archived) |
| `start_date` | DATE | Academic year start date |
| `end_date` | DATE | Academic year end date |
| `number_of_terms` | INT | Number of terms in the year |
| `created_at` | DATETIME | Creation timestamp |

### `academic_year_terms`
Stores term information for each academic year.

| Column | Type | Description |
|--------|------|-------------|
| `term_id` | INT(11) PK | Primary key |
| `academic_id` | INT(11) FK | Foreign key to `academic_years` |
| `term_number` | INT | Term number (1, 2, 3, etc.) |
| `term_name` | VARCHAR(50) | Term name |
| `start_date` | DATE | Term start date |
| `end_date` | DATE | Term end date |

### `subjects`
Stores subject/course information.

| Column | Type | Description |
|--------|------|-------------|
| `subject_id` | INT(11) PK | Primary key |
| `subject_name` | VARCHAR(100) | Subject name |
| `subject_code` | VARCHAR(20) | Subject code |
| `level` | ENUM | Education level (Primary, JHS, SHS) |
| `category` | ENUM | Subject category (Core, Elective) |
| `is_active` | TINYINT(1) | Active status flag |
| `created_at` | DATETIME | Creation timestamp |

### `classes`
Stores class/grade information.

| Column | Type | Description |
|--------|------|-------------|
| `class_id` | INT(11) PK | Primary key |
| `class_name` | VARCHAR(50) | Class name |
| `class_level_id` | INT(11) FK | Foreign key to `class_levels` |
| `capacity` | INT | Maximum student capacity |

### `class_levels`
Defines educational levels and grades.

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT(11) PK | Primary key |
| `level` | ENUM | Education level (Primary, JHS, SHS) |
| `grade` | INT | Grade number |
| `display_name` | VARCHAR(50) | Display name (e.g., "Primary 1") |

### `class_subjects`
Maps subjects to classes (many-to-many).

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT(11) PK | Primary key |
| `class_id` | INT(11) FK | Foreign key to `classes` |
| `subject_id` | INT(11) FK | Foreign key to `subjects` |
| `academic_id` | INT(11) FK | Foreign key to `academic_years` |

### `grading_scheme`
Stores grading schemes and grade boundaries.

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT(11) PK | Primary key |
| `grade` | VARCHAR(5) | Grade label (A, B, C, etc.) |
| `min_score` | DECIMAL(5,2) | Minimum score for grade |
| `max_score` | DECIMAL(5,2) | Maximum score for grade |
| `remarks` | VARCHAR(100) | Grade remarks |
| `added_by` | VARCHAR(20) FK | Foreign key to `users.username` |
| `created_at` | DATETIME | Creation timestamp |

### `assignment_activities`
Stores assignment and activity definitions.

| Column | Type | Description |
|--------|------|-------------|
| `activity_id` | INT(11) PK | Primary key |
| `activity_name` | VARCHAR(100) | Activity name |
| `max_score` | DECIMAL(5,2) | Maximum possible score |
| `academic_id` | INT(11) FK | Foreign key to `academic_years` |
| `term_id` | INT(11) FK | Foreign key to `academic_year_terms` |
| `status` | ENUM | Activity status (active, inactive) |
| `created_at` | DATETIME | Creation timestamp |

### `class_activity_assignment`
Maps activities to classes.

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT(11) PK | Primary key |
| `class_id` | INT(11) FK | Foreign key to `classes` |
| `activity_id` | INT(11) FK | Foreign key to `assignment_activities` |
| `subject_id` | INT(11) FK | Foreign key to `subjects` |
| `status` | ENUM | Assignment status |

### `promotion_criteria`
Defines criteria for student promotion.

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT(11) PK | Primary key |
| `level` | ENUM | Education level |
| `core_subjects` | JSON | Array of core subject IDs |
| `elective_subjects` | JSON | Array of elective subject IDs |
| `min_core_average` | DECIMAL(5,2) | Minimum core subject average |
| `min_elective_average` | DECIMAL(5,2) | Minimum elective average |
| `created_at` | DATETIME | Creation timestamp |

---

## Student Management

### `students`
Stores student information.

| Column | Type | Description |
|--------|------|-------------|
| `student_no` | VARCHAR(20) PK | Student number (primary key) |
| `first_name` | VARCHAR(50) | Student first name |
| `last_name` | VARCHAR(50) | Student last name |
| `other_name` | VARCHAR(50) | Other names |
| `date_of_birth` | DATE | Date of birth |
| `gender` | ENUM('Male','Female') | Gender |
| `class_id` | INT(11) FK | Foreign key to `classes` |
| `admission_date` | DATE | Date of admission |
| `status` | ENUM | Student status (active, graduated, transferred, etc.) |
| `created_at` | DATETIME | Record creation timestamp |

**Indexes**:
- PRIMARY KEY (`student_no`)
- INDEX (`class_id`, `status`)

### `student_promotions`
Tracks student promotion history.

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT(11) PK | Primary key |
| `student_no` | VARCHAR(20) FK | Foreign key to `students` |
| `from_class_id` | INT(11) FK | Previous class |
| `to_class_id` | INT(11) FK | New class |
| `academic_id` | INT(11) FK | Academic year of promotion |
| `promotion_type` | ENUM | Type (normal, special, graduated) |
| `promoted_at` | DATETIME | Promotion timestamp |

### `guardian_info`
Stores guardian/parent information.

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT(11) PK | Primary key |
| `student_no` | VARCHAR(20) FK | Foreign key to `students` |
| `guardian_name` | VARCHAR(100) | Guardian full name |
| `relationship` | VARCHAR(50) | Relationship to student |
| `phone` | VARCHAR(20) | Contact phone |
| `email` | VARCHAR(100) | Contact email |

### `emergency_contact`
Stores emergency contact information.

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT(11) PK | Primary key |
| `student_no` | VARCHAR(20) FK | Foreign key to `students` |
| `contact_name` | VARCHAR(100) | Emergency contact name |
| `relationship` | VARCHAR(50) | Relationship to student |
| `phone` | VARCHAR(20) | Contact phone |

---

## Staff Management

### `staff`
Stores staff member information.

| Column | Type | Description |
|--------|------|-------------|
| `staff_id` | VARCHAR(20) PK | Staff identifier |
| `first_name` | VARCHAR(50) | First name |
| `last_name` | VARCHAR(50) | Last name |
| `email` | VARCHAR(100) | Email address |
| `phone` | VARCHAR(20) | Phone number |
| `hire_date` | DATE | Date of hire |
| `status` | ENUM | Employment status |
| `created_at` | DATETIME | Record creation timestamp |

---

## Financial Management

### `fees`
Stores fee structure definitions.

| Column | Type | Description |
|--------|------|-------------|
| `fee_id` | INT(11) PK | Primary key |
| `fee_name` | VARCHAR(100) | Fee name |
| `amount` | DECIMAL(10,2) | Fee amount |
| `academic_id` | INT(11) FK | Foreign key to `academic_years` |

### `payment_history`
Tracks student payments.

| Column | Type | Description |
|--------|------|-------------|
| `payment_id` | INT(11) PK | Primary key |
| `student_no` | VARCHAR(20) FK | Foreign key to `students` |
| `amount` | DECIMAL(10,2) | Payment amount |
| `payment_date` | DATE | Date of payment |
| `payment_method` | VARCHAR(50) | Payment method |
| `reference` | VARCHAR(100) | Payment reference |

---

## Security & Audit

### `api_keys`
Stores API authentication keys.

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT(11) PK | Primary key |
| `key_value` | VARCHAR(255) UNIQUE | API key value |
| `user_id` | INT(11) FK | Foreign key to `users` |
| `is_active` | TINYINT(1) | Active status |
| `created_at` | DATETIME | Creation timestamp |
| `expires_at` | DATETIME | Expiration timestamp |

### `auth_logs`
Logs authentication events.

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT(11) PK | Primary key |
| `user_id` | VARCHAR(20) | User identifier |
| `action` | VARCHAR(50) | Action performed (login, logout, etc.) |
| `status` | VARCHAR(20) | Action status (success, failure) |
| `ip_address` | VARCHAR(45) | Client IP address |
| `user_agent` | TEXT | Client user agent |
| `created_at` | DATETIME | Event timestamp |

### `audit_logs`
Logs system audit events.

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT(11) PK | Primary key |
| `user_id` | VARCHAR(20) | User who performed action |
| `action` | VARCHAR(100) | Action performed |
| `table_name` | VARCHAR(50) | Affected table |
| `record_id` | VARCHAR(50) | Affected record ID |
| `old_values` | JSON | Previous values |
| `new_values` | JSON | New values |
| `ip_address` | VARCHAR(45) | Client IP |
| `created_at` | DATETIME | Event timestamp |

### `blacklisted_tokens`
Stores invalidated JWT tokens.

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT(11) PK | Primary key |
| `token` | TEXT | JWT token value |
| `blacklisted_at` | DATETIME | Blacklist timestamp |

---

## Supporting Tables

### `migrations`
Tracks applied database migrations.

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT(11) PK | Primary key |
| `migration` | VARCHAR(255) | Migration filename |
| `batch` | INT | Migration batch number |
| `executed_at` | DATETIME | Execution timestamp |

### `countries`
Reference table for countries.

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT(11) PK | Primary key |
| `name` | VARCHAR(100) | Country name |
| `code` | VARCHAR(3) | ISO country code |

---

## Database Views

The system includes several views for simplified data access:

### `vw_student_profile`
Consolidated student information with class and guardian details.

### `vw_staff_profile`
Consolidated staff information with role and department details.

### `vw_secure_users`
User information with sensitive data masked.

### `vw_class_performance`
Aggregated class performance metrics.

### `vw_financial_summary`
Student financial status summary.

---

## Relationships

### Key Foreign Key Constraints

1. **User-Role Relationship**
   - `users.role_id` → `roles.role_id`

2. **Academic Relationships**
   - `academic_year_terms.academic_id` → `academic_years.academic_id`
   - `class_subjects.class_id` → `classes.class_id`
   - `class_subjects.subject_id` → `subjects.subject_id`
   - `class_subjects.academic_id` → `academic_years.academic_id`

3. **Student Relationships**
   - `students.class_id` → `classes.class_id`
   - `guardian_info.student_no` → `students.student_no`
   - `student_promotions.student_no` → `students.student_no`

4. **Activity Relationships**
   - `class_activity_assignment.class_id` → `classes.class_id`
   - `class_activity_assignment.activity_id` → `assignment_activities.activity_id`
   - `class_activity_assignment.subject_id` → `subjects.subject_id`

5. **Grading Relationships**
   - `grading_scheme.added_by` → `users.username`

---

## Recent Schema Changes

### December 2025

#### Account Lockout Implementation (2025-12-26)
Added to `users` table:
- `failed_login_attempts` INT(11) DEFAULT 0
- `locked_until` DATETIME NULL
- `locked_by_admin` TINYINT(1) DEFAULT 0
- INDEX on `locked_until`

Purpose: Implement automatic account lockout after 5 failed login attempts.

#### Database Integrity Fixes (2025-12-21)
- Added foreign key constraints across multiple tables
- Created indexes for performance optimization
- Cleaned up orphaned data in `grading_scheme`
- Applied CASCADE rules for referential integrity

#### Assignment Activities Enhancement (2025-12-19)
- Added `academic_id` and `term_id` to `assignment_activities`
- Added `status` column to `assignment_activities`
- Added `status` column to `class_activity_assignment`

---

## Performance Considerations

### Indexes
- All foreign keys have corresponding indexes
- Frequently queried columns (status, dates) are indexed
- Unique constraints on natural keys (email, username, etc.)

### Optimization Tips
- Use prepared statements for all queries
- Leverage views for complex joins
- Regular ANALYZE TABLE for query optimization
- Monitor slow query log

---

## Backup and Maintenance

### Recommended Practices
- Daily automated backups
- Weekly full backups
- Monthly backup verification
- Regular OPTIMIZE TABLE operations
- Monitor table sizes and growth

### Migration Management
- All schema changes via migrations
- Migrations tracked in `migrations` table
- Rollback capability for all migrations
- Test migrations in staging before production

---

**Last Updated**: December 2025  
**Schema Version**: 1.0.0
