-- =====================================================
-- Performance Indexes Migration
-- Priority 2: Database Optimization
-- Created: February 22, 2026
-- =====================================================

-- This migration adds indexes to improve query performance
-- Run this after backing up your database

-- =====================================================
-- STUDENTS TABLE INDEXES
-- =====================================================

-- Index on class_id for faster class-based queries
CREATE INDEX IF NOT EXISTS idx_students_class_id 
ON students(class_id);

-- Index on status for filtering active/inactive students
CREATE INDEX IF NOT EXISTS idx_students_status 
ON students(status);

-- Composite index for common query patterns
CREATE INDEX IF NOT EXISTS idx_students_class_status 
ON students(class_id, status);

-- =====================================================
-- STUDENT_CONTACT TABLE INDEXES
-- =====================================================

-- Index on student_no for JOIN operations
CREATE INDEX IF NOT EXISTS idx_student_contact_student_no 
ON student_contact(student_no);

-- Index on email for login/search operations
CREATE INDEX IF NOT EXISTS idx_student_contact_email 
ON student_contact(email);

-- =====================================================
-- ADMISSION_DETAILS TABLE INDEXES
-- =====================================================

-- Index on student_no for JOIN operations
CREATE INDEX IF NOT EXISTS idx_admission_student_no 
ON admission_details(student_no);

-- Index on admission_status for filtering
CREATE INDEX IF NOT EXISTS idx_admission_status 
ON admission_details(admission_status);

-- Index on class_assigned for class-based queries
CREATE INDEX IF NOT EXISTS idx_admission_class_assigned 
ON admission_details(class_assigned);

-- Composite index for common query patterns
CREATE INDEX IF NOT EXISTS idx_admission_status_class 
ON admission_details(admission_status, class_assigned);

-- =====================================================
-- STUDENT_SCORES TABLE INDEXES
-- =====================================================

-- Index on student_no for student score queries
CREATE INDEX IF NOT EXISTS idx_scores_student_no 
ON student_scores(student_no);

-- Index on subject_id for subject-based queries
CREATE INDEX IF NOT EXISTS idx_scores_subject_id 
ON student_scores(subject_id);

-- Index on class_id for class performance queries
CREATE INDEX IF NOT EXISTS idx_scores_class_id 
ON student_scores(class_id);

-- Composite index for academic year/term queries
CREATE INDEX IF NOT EXISTS idx_scores_academic_term 
ON student_scores(academic_year, term);

-- Composite index for common query patterns
CREATE INDEX IF NOT EXISTS idx_scores_student_academic 
ON student_scores(student_no, academic_year, term);

-- Composite index for class performance queries
CREATE INDEX IF NOT EXISTS idx_scores_class_subject_academic 
ON student_scores(class_id, subject_id, academic_year, term);

-- =====================================================
-- CLASS_SUBJECTS TABLE INDEXES
-- =====================================================

-- Index on class_id for class subject queries
CREATE INDEX IF NOT EXISTS idx_class_subjects_class_id 
ON class_subjects(class_id);

-- Index on subject_id for subject assignment queries
CREATE INDEX IF NOT EXISTS idx_class_subjects_subject_id 
ON class_subjects(subject_id);

-- Index on academic_id for academic year filtering
CREATE INDEX IF NOT EXISTS idx_class_subjects_academic_id 
ON class_subjects(academic_id);

-- Composite index for unique constraint checking
CREATE INDEX IF NOT EXISTS idx_class_subjects_unique 
ON class_subjects(class_id, subject_id, academic_id);

-- =====================================================
-- TEACHER_SUBJECTS TABLE INDEXES
-- =====================================================

-- Index on staff_id for teacher queries
CREATE INDEX IF NOT EXISTS idx_teacher_subjects_staff_id 
ON teacher_subjects(staff_id);

-- Index on subject_id for subject assignment queries
CREATE INDEX IF NOT EXISTS idx_teacher_subjects_subject_id 
ON teacher_subjects(subject_id);

-- Index on class_id for class-based queries
CREATE INDEX IF NOT EXISTS idx_teacher_subjects_class_id 
ON teacher_subjects(class_id);

-- =====================================================
-- GUARDIAN_INFO TABLE INDEXES
-- =====================================================

-- Index on student_no for guardian queries
CREATE INDEX IF NOT EXISTS idx_guardian_student_no 
ON guardian_info(student_no);

-- Index on phone for contact searches
CREATE INDEX IF NOT EXISTS idx_guardian_phone 
ON guardian_info(phone);

-- =====================================================
-- EMERGENCY_CONTACT TABLE INDEXES
-- =====================================================

-- Index on student_no for emergency contact queries
CREATE INDEX IF NOT EXISTS idx_emergency_student_no 
ON emergency_contact(student_no);

-- =====================================================
-- USERS TABLE INDEXES
-- =====================================================

-- Index on email for login operations (if not already unique)
CREATE INDEX IF NOT EXISTS idx_users_email 
ON users(email);

-- Index on role_id for role-based queries
CREATE INDEX IF NOT EXISTS idx_users_role_id 
ON users(role_id);

-- Index on status for active user filtering
CREATE INDEX IF NOT EXISTS idx_users_status 
ON users(status);

-- Index on locked_until for lockout checks
CREATE INDEX IF NOT EXISTS idx_users_locked_until 
ON users(locked_until);

-- =====================================================
-- ACADEMIC_YEARS TABLE INDEXES
-- =====================================================

-- Index on status for active year queries
CREATE INDEX IF NOT EXISTS idx_academic_years_status 
ON academic_years(status);

-- Index on academic_year for year-based queries
CREATE INDEX IF NOT EXISTS idx_academic_years_year 
ON academic_years(academic_year);

-- =====================================================
-- ACADEMIC_YEAR_TERMS TABLE INDEXES
-- =====================================================

-- Index on academic_id for term queries
CREATE INDEX IF NOT EXISTS idx_academic_terms_academic_id 
ON academic_year_terms(academic_id);

-- Composite index for term lookups
CREATE INDEX IF NOT EXISTS idx_academic_terms_year_term 
ON academic_year_terms(academic_id, term_number);

-- =====================================================
-- SUBJECTS TABLE INDEXES
-- =====================================================

-- Index on level for level-based filtering
CREATE INDEX IF NOT EXISTS idx_subjects_level 
ON subjects(level);

-- Index on category for category filtering
CREATE INDEX IF NOT EXISTS idx_subjects_category 
ON subjects(category);

-- Index on is_active for active subject filtering
CREATE INDEX IF NOT EXISTS idx_subjects_is_active 
ON subjects(is_active);

-- Composite index for common queries
CREATE INDEX IF NOT EXISTS idx_subjects_level_category 
ON subjects(level, category, is_active);

-- =====================================================
-- CLASSES TABLE INDEXES
-- =====================================================

-- Index on class_level_id for level-based queries
CREATE INDEX IF NOT EXISTS idx_classes_level_id 
ON classes(class_level_id);

-- =====================================================
-- ASSIGNMENT_ACTIVITIES TABLE INDEXES
-- =====================================================

-- Index on academic_id for academic year filtering
CREATE INDEX IF NOT EXISTS idx_activities_academic_id 
ON assignment_activities(academic_id);

-- Index on term_id for term filtering
CREATE INDEX IF NOT EXISTS idx_activities_term_id 
ON assignment_activities(term_id);

-- Index on status for active activity filtering
CREATE INDEX IF NOT EXISTS idx_activities_status 
ON assignment_activities(status);

-- Composite index for common queries
CREATE INDEX IF NOT EXISTS idx_activities_academic_term 
ON assignment_activities(academic_id, term_id, status);

-- =====================================================
-- CLASS_ACTIVITY_ASSIGNMENT TABLE INDEXES
-- =====================================================

-- Index on class_id for class activity queries
CREATE INDEX IF NOT EXISTS idx_class_activity_class_id 
ON class_activity_assignment(class_id);

-- Index on activity_id for activity assignment queries
CREATE INDEX IF NOT EXISTS idx_class_activity_activity_id 
ON class_activity_assignment(activity_id);

-- Index on subject_id for subject-based queries
CREATE INDEX IF NOT EXISTS idx_class_activity_subject_id 
ON class_activity_assignment(subject_id);

-- Composite index for unique constraint checking
CREATE INDEX IF NOT EXISTS idx_class_activity_unique 
ON class_activity_assignment(class_id, activity_id, subject_id);

-- =====================================================
-- AUDIT_LOGS TABLE INDEXES
-- =====================================================

-- Index on user_id for user activity tracking
CREATE INDEX IF NOT EXISTS idx_audit_user_id 
ON audit_logs(user_id);

-- Index on table_name for table-specific audits
CREATE INDEX IF NOT EXISTS idx_audit_table_name 
ON audit_logs(table_name);

-- Index on created_at for time-based queries
CREATE INDEX IF NOT EXISTS idx_audit_created_at 
ON audit_logs(created_at);

-- Composite index for common audit queries
CREATE INDEX IF NOT EXISTS idx_audit_user_table_date 
ON audit_logs(user_id, table_name, created_at);

-- =====================================================
-- AUTH_LOGS TABLE INDEXES
-- =====================================================

-- Index on user_id for user login history
CREATE INDEX IF NOT EXISTS idx_auth_logs_user_id 
ON auth_logs(user_id);

-- Index on action for action-based filtering
CREATE INDEX IF NOT EXISTS idx_auth_logs_action 
ON auth_logs(action);

-- Index on status for success/failure filtering
CREATE INDEX IF NOT EXISTS idx_auth_logs_status 
ON auth_logs(status);

-- Index on created_at for time-based queries
CREATE INDEX IF NOT EXISTS idx_auth_logs_created_at 
ON auth_logs(created_at);

-- Composite index for security monitoring
CREATE INDEX IF NOT EXISTS idx_auth_logs_user_status_date 
ON auth_logs(user_id, status, created_at);

-- =====================================================
-- VERIFICATION QUERIES
-- =====================================================

-- Run these queries to verify indexes were created successfully

-- Show all indexes on students table
-- SHOW INDEX FROM students;

-- Show all indexes on student_scores table
-- SHOW INDEX FROM student_scores;

-- Analyze table statistics after index creation
-- ANALYZE TABLE students;
-- ANALYZE TABLE student_scores;
-- ANALYZE TABLE admission_details;

-- =====================================================
-- PERFORMANCE TESTING
-- =====================================================

-- Test query performance before and after indexes
-- Example: Student list with class and status filter
-- EXPLAIN SELECT s.*, c.phone, c.email, a.admission_status, cl.class_name
-- FROM students s
-- LEFT JOIN student_contact c ON s.student_no = c.student_no
-- LEFT JOIN admission_details a ON s.student_no = a.student_no
-- LEFT JOIN classes cl ON a.class_assigned = cl.class_id
-- WHERE a.admission_status = 'active' AND s.class_id = 1;

-- =====================================================
-- ROLLBACK SCRIPT
-- =====================================================

-- To remove all indexes created by this migration, run:
-- DROP INDEX IF EXISTS idx_students_class_id ON students;
-- DROP INDEX IF EXISTS idx_students_status ON students;
-- ... (continue for all indexes)

-- =====================================================
-- NOTES
-- =====================================================

-- 1. Run ANALYZE TABLE after creating indexes
-- 2. Monitor query performance with EXPLAIN
-- 3. Indexes increase write time slightly but dramatically improve read performance
-- 4. Consider removing unused indexes if they impact write performance
-- 5. Regularly run OPTIMIZE TABLE to maintain index efficiency

-- =====================================================
-- END OF MIGRATION
-- =====================================================
