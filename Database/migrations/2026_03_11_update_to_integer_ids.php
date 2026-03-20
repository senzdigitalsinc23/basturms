<?php

use Database\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Drop existing tables in reverse order of dependencies
        $this->db->exec("SET FOREIGN_KEY_CHECKS = 0");
        
        $this->db->exec("DROP TABLE IF EXISTS staff_disciplinary_records");
        $this->db->exec("DROP TABLE IF EXISTS staff_training_records");
        $this->db->exec("DROP TABLE IF EXISTS staff_performance_reviews");
        $this->db->exec("DROP TABLE IF EXISTS staff_leave_records");
        $this->db->exec("DROP TABLE IF EXISTS staff_documents");
        $this->db->exec("DROP TABLE IF EXISTS staff_dependents");
        $this->db->exec("DROP TABLE IF EXISTS staff_bank_info");
        $this->db->exec("DROP TABLE IF EXISTS staff_work_experience");
        $this->db->exec("DROP TABLE IF EXISTS staff_certifications");
        $this->db->exec("DROP TABLE IF EXISTS staff_qualifications");
        $this->db->exec("DROP TABLE IF EXISTS staff_employment_info");
        $this->db->exec("DROP TABLE IF EXISTS departments");
        $this->db->exec("DROP TABLE IF EXISTS staff_emergency_contacts");
        $this->db->exec("DROP TABLE IF EXISTS staff_contact_info");
        $this->db->exec("DROP TABLE IF EXISTS staff_personal_info");
        $this->db->exec("DROP TABLE IF EXISTS validations");
        $this->db->exec("DROP TABLE IF EXISTS validation_staff");
        $this->db->exec("DROP TABLE IF EXISTS units");
        
        $this->db->exec("SET FOREIGN_KEY_CHECKS = 1");

        // Recreate all tables with INT AUTO_INCREMENT IDs

        // 1. Units table
        $this->db->exec("
            CREATE TABLE units (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL UNIQUE,
                description TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                deleted_at TIMESTAMP NULL,
                INDEX idx_name (name),
                INDEX idx_deleted_at (deleted_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // 2. Departments table
        $this->db->exec("
            CREATE TABLE departments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL UNIQUE,
                code VARCHAR(50) UNIQUE,
                description TEXT,
                parent_department_id INT,
                head_of_department INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                deleted_at TIMESTAMP NULL,
                INDEX idx_name (name),
                INDEX idx_code (code)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // 3. Main staff table
        $this->db->exec("
            CREATE TABLE validation_staff (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                role ENUM('staff', 'incharge', 'accountant', 'admin') NOT NULL DEFAULT 'staff',
                unit_id INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                deleted_at TIMESTAMP NULL,
                FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE SET NULL,
                INDEX idx_email (email),
                INDEX idx_role (role),
                INDEX idx_unit_id (unit_id),
                INDEX idx_deleted_at (deleted_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Add foreign keys to departments
        $this->db->exec("
            ALTER TABLE departments 
            ADD CONSTRAINT fk_parent_department 
            FOREIGN KEY (parent_department_id) REFERENCES departments(id) ON DELETE SET NULL
        ");
        
        $this->db->exec("
            ALTER TABLE departments 
            ADD CONSTRAINT fk_head_of_department 
            FOREIGN KEY (head_of_department) REFERENCES validation_staff(id) ON DELETE SET NULL
        ");

        // 4. Staff Personal Information
        $this->db->exec("
            CREATE TABLE staff_personal_info (
                id INT AUTO_INCREMENT PRIMARY KEY,
                staff_id INT NOT NULL UNIQUE,
                title ENUM('Mr', 'Mrs', 'Miss', 'Dr', 'Prof', 'Rev', 'Hon') DEFAULT 'Mr',
                first_name VARCHAR(100) NOT NULL,
                middle_name VARCHAR(100),
                last_name VARCHAR(100) NOT NULL,
                maiden_name VARCHAR(100),
                date_of_birth DATE NOT NULL,
                gender ENUM('Male', 'Female', 'Other') NOT NULL,
                marital_status ENUM('Single', 'Married', 'Divorced', 'Widowed', 'Separated') DEFAULT 'Single',
                nationality VARCHAR(100) DEFAULT 'Ghanaian',
                national_id_type ENUM('Ghana Card', 'Passport', 'Voters ID', 'Drivers License', 'SSNIT') DEFAULT 'Ghana Card',
                national_id_number VARCHAR(50),
                ssnit_number VARCHAR(50),
                tin_number VARCHAR(50),
                profile_photo_url VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (staff_id) REFERENCES validation_staff(id) ON DELETE CASCADE,
                INDEX idx_staff_id (staff_id),
                INDEX idx_national_id (national_id_number),
                INDEX idx_ssnit (ssnit_number)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // 5. Staff Contact Information
        $this->db->exec("
            CREATE TABLE staff_contact_info (
                id INT AUTO_INCREMENT PRIMARY KEY,
                staff_id INT NOT NULL UNIQUE,
                primary_phone VARCHAR(20) NOT NULL,
                secondary_phone VARCHAR(20),
                personal_email VARCHAR(255),
                work_email VARCHAR(255),
                residential_address TEXT NOT NULL,
                residential_city VARCHAR(100),
                residential_region VARCHAR(100),
                residential_gps_address VARCHAR(50),
                postal_address TEXT,
                hometown VARCHAR(100),
                home_region VARCHAR(100),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (staff_id) REFERENCES validation_staff(id) ON DELETE CASCADE,
                INDEX idx_staff_id (staff_id),
                INDEX idx_primary_phone (primary_phone)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // 6. Staff Emergency Contacts
        $this->db->exec("
            CREATE TABLE staff_emergency_contacts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                staff_id INT NOT NULL,
                contact_name VARCHAR(255) NOT NULL,
                relationship VARCHAR(100) NOT NULL,
                phone_number VARCHAR(20) NOT NULL,
                alternative_phone VARCHAR(20),
                address TEXT,
                is_primary BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (staff_id) REFERENCES validation_staff(id) ON DELETE CASCADE,
                INDEX idx_staff_id (staff_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // 7. Staff Employment Information
        $this->db->exec("
            CREATE TABLE staff_employment_info (
                id INT AUTO_INCREMENT PRIMARY KEY,
                staff_id INT NOT NULL UNIQUE,
                employee_number VARCHAR(50) UNIQUE,
                staff_category ENUM('Senior Staff', 'Junior Staff', 'Senior Member', 'Contract', 'National Service', 'Casual') NOT NULL,
                employment_type ENUM('Permanent', 'Contract', 'Temporary', 'Part-Time', 'Casual') NOT NULL,
                employment_status ENUM('Active', 'On Leave', 'Suspended', 'Terminated', 'Retired', 'Resigned') DEFAULT 'Active',
                date_of_first_appointment DATE NOT NULL,
                date_of_current_appointment DATE,
                confirmation_date DATE,
                retirement_date DATE,
                department_id INT,
                unit_id INT,
                position_title VARCHAR(255) NOT NULL,
                job_grade VARCHAR(50),
                salary_grade VARCHAR(50),
                step_level INT,
                reports_to INT,
                work_location VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (staff_id) REFERENCES validation_staff(id) ON DELETE CASCADE,
                FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
                FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE SET NULL,
                FOREIGN KEY (reports_to) REFERENCES validation_staff(id) ON DELETE SET NULL,
                INDEX idx_staff_id (staff_id),
                INDEX idx_employee_number (employee_number),
                INDEX idx_employment_status (employment_status),
                INDEX idx_unit_id (unit_id),
                INDEX idx_department_id (department_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // 8. Staff Educational Qualifications
        $this->db->exec("
            CREATE TABLE staff_qualifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                staff_id INT NOT NULL,
                qualification_type ENUM('Basic', 'Secondary', 'Diploma', 'Degree', 'Masters', 'PhD', 'Professional', 'Certificate') NOT NULL,
                institution_name VARCHAR(255) NOT NULL,
                qualification_name VARCHAR(255) NOT NULL,
                field_of_study VARCHAR(255),
                grade_obtained VARCHAR(50),
                start_date DATE,
                completion_date DATE,
                certificate_number VARCHAR(100),
                is_highest_qualification BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (staff_id) REFERENCES validation_staff(id) ON DELETE CASCADE,
                INDEX idx_staff_id (staff_id),
                INDEX idx_qualification_type (qualification_type)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // 9. Staff Professional Certifications
        $this->db->exec("
            CREATE TABLE staff_certifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                staff_id INT NOT NULL,
                certification_name VARCHAR(255) NOT NULL,
                issuing_organization VARCHAR(255) NOT NULL,
                certification_number VARCHAR(100),
                issue_date DATE NOT NULL,
                expiry_date DATE,
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (staff_id) REFERENCES validation_staff(id) ON DELETE CASCADE,
                INDEX idx_staff_id (staff_id),
                INDEX idx_expiry_date (expiry_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // 10. Staff Work Experience
        $this->db->exec("
            CREATE TABLE staff_work_experience (
                id INT AUTO_INCREMENT PRIMARY KEY,
                staff_id INT NOT NULL,
                employer_name VARCHAR(255) NOT NULL,
                position_held VARCHAR(255) NOT NULL,
                start_date DATE NOT NULL,
                end_date DATE,
                responsibilities TEXT,
                reason_for_leaving VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (staff_id) REFERENCES validation_staff(id) ON DELETE CASCADE,
                INDEX idx_staff_id (staff_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // 11. Staff Bank Information
        $this->db->exec("
            CREATE TABLE staff_bank_info (
                id INT AUTO_INCREMENT PRIMARY KEY,
                staff_id INT NOT NULL,
                bank_name VARCHAR(255) NOT NULL,
                branch_name VARCHAR(255),
                account_number VARCHAR(50) NOT NULL,
                account_name VARCHAR(255) NOT NULL,
                account_type ENUM('Savings', 'Current', 'Fixed Deposit') DEFAULT 'Savings',
                is_primary BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (staff_id) REFERENCES validation_staff(id) ON DELETE CASCADE,
                INDEX idx_staff_id (staff_id),
                INDEX idx_account_number (account_number)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // 12. Staff Dependents
        $this->db->exec("
            CREATE TABLE staff_dependents (
                id INT AUTO_INCREMENT PRIMARY KEY,
                staff_id INT NOT NULL,
                full_name VARCHAR(255) NOT NULL,
                relationship ENUM('Spouse', 'Child', 'Parent', 'Sibling', 'Other') NOT NULL,
                date_of_birth DATE,
                gender ENUM('Male', 'Female', 'Other'),
                is_beneficiary BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (staff_id) REFERENCES validation_staff(id) ON DELETE CASCADE,
                INDEX idx_staff_id (staff_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // 13. Staff Documents
        $this->db->exec("
            CREATE TABLE staff_documents (
                id INT AUTO_INCREMENT PRIMARY KEY,
                staff_id INT NOT NULL,
                document_type ENUM('CV', 'Certificate', 'Transcript', 'ID Card', 'Passport', 'Birth Certificate', 'Marriage Certificate', 'Reference Letter', 'Medical Report', 'Other') NOT NULL,
                document_name VARCHAR(255) NOT NULL,
                file_path VARCHAR(255) NOT NULL,
                file_size INT,
                mime_type VARCHAR(100),
                uploaded_by INT,
                verified BOOLEAN DEFAULT FALSE,
                verified_by INT,
                verified_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (staff_id) REFERENCES validation_staff(id) ON DELETE CASCADE,
                FOREIGN KEY (uploaded_by) REFERENCES validation_staff(id) ON DELETE SET NULL,
                FOREIGN KEY (verified_by) REFERENCES validation_staff(id) ON DELETE SET NULL,
                INDEX idx_staff_id (staff_id),
                INDEX idx_document_type (document_type)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // 14. Staff Leave Records
        $this->db->exec("
            CREATE TABLE staff_leave_records (
                id INT AUTO_INCREMENT PRIMARY KEY,
                staff_id INT NOT NULL,
                leave_type ENUM('Annual', 'Sick', 'Maternity', 'Paternity', 'Study', 'Compassionate', 'Unpaid', 'Other') NOT NULL,
                start_date DATE NOT NULL,
                end_date DATE NOT NULL,
                days_taken INT NOT NULL,
                reason TEXT,
                status ENUM('Pending', 'Approved', 'Rejected', 'Cancelled') DEFAULT 'Pending',
                approved_by INT,
                approved_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (staff_id) REFERENCES validation_staff(id) ON DELETE CASCADE,
                FOREIGN KEY (approved_by) REFERENCES validation_staff(id) ON DELETE SET NULL,
                INDEX idx_staff_id (staff_id),
                INDEX idx_status (status),
                INDEX idx_dates (start_date, end_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // 15. Staff Performance Reviews
        $this->db->exec("
            CREATE TABLE staff_performance_reviews (
                id INT AUTO_INCREMENT PRIMARY KEY,
                staff_id INT NOT NULL,
                review_period_start DATE NOT NULL,
                review_period_end DATE NOT NULL,
                reviewer_id INT NOT NULL,
                overall_rating DECIMAL(3,2),
                strengths TEXT,
                areas_for_improvement TEXT,
                goals_for_next_period TEXT,
                comments TEXT,
                status ENUM('Draft', 'Submitted', 'Reviewed', 'Acknowledged') DEFAULT 'Draft',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (staff_id) REFERENCES validation_staff(id) ON DELETE CASCADE,
                FOREIGN KEY (reviewer_id) REFERENCES validation_staff(id) ON DELETE CASCADE,
                INDEX idx_staff_id (staff_id),
                INDEX idx_review_period (review_period_start, review_period_end)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // 16. Staff Training Records
        $this->db->exec("
            CREATE TABLE staff_training_records (
                id INT AUTO_INCREMENT PRIMARY KEY,
                staff_id INT NOT NULL,
                training_title VARCHAR(255) NOT NULL,
                training_provider VARCHAR(255),
                training_type ENUM('Internal', 'External', 'Online', 'Workshop', 'Conference', 'Seminar') NOT NULL,
                start_date DATE NOT NULL,
                end_date DATE NOT NULL,
                duration_hours INT,
                cost DECIMAL(10,2),
                certificate_obtained BOOLEAN DEFAULT FALSE,
                certificate_number VARCHAR(100),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (staff_id) REFERENCES validation_staff(id) ON DELETE CASCADE,
                INDEX idx_staff_id (staff_id),
                INDEX idx_training_type (training_type)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // 17. Staff Disciplinary Records
        $this->db->exec("
            CREATE TABLE staff_disciplinary_records (
                id INT AUTO_INCREMENT PRIMARY KEY,
                staff_id INT NOT NULL,
                incident_date DATE NOT NULL,
                incident_type ENUM('Lateness', 'Absenteeism', 'Misconduct', 'Insubordination', 'Policy Violation', 'Other') NOT NULL,
                description TEXT NOT NULL,
                action_taken ENUM('Verbal Warning', 'Written Warning', 'Suspension', 'Demotion', 'Termination', 'Other') NOT NULL,
                issued_by INT NOT NULL,
                status ENUM('Active', 'Resolved', 'Appealed', 'Overturned') DEFAULT 'Active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (staff_id) REFERENCES validation_staff(id) ON DELETE CASCADE,
                FOREIGN KEY (issued_by) REFERENCES validation_staff(id) ON DELETE CASCADE,
                INDEX idx_staff_id (staff_id),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // 18. Validations table
        $this->db->exec("
            CREATE TABLE validations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                staff_id INT NOT NULL,
                month VARCHAR(20) NOT NULL,
                year INT NOT NULL,
                validated BOOLEAN DEFAULT FALSE,
                validated_by INT,
                validated_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (staff_id) REFERENCES validation_staff(id) ON DELETE CASCADE,
                FOREIGN KEY (validated_by) REFERENCES validation_staff(id) ON DELETE SET NULL,
                UNIQUE KEY unique_staff_month_year (staff_id, month, year),
                INDEX idx_staff_id (staff_id),
                INDEX idx_month_year (month, year),
                INDEX idx_validated (validated),
                INDEX idx_validated_by (validated_by)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(): void
    {
        $this->db->exec("SET FOREIGN_KEY_CHECKS = 0");
        
        $this->db->exec("DROP TABLE IF EXISTS validations");
        $this->db->exec("DROP TABLE IF EXISTS staff_disciplinary_records");
        $this->db->exec("DROP TABLE IF EXISTS staff_training_records");
        $this->db->exec("DROP TABLE IF EXISTS staff_performance_reviews");
        $this->db->exec("DROP TABLE IF EXISTS staff_leave_records");
        $this->db->exec("DROP TABLE IF EXISTS staff_documents");
        $this->db->exec("DROP TABLE IF EXISTS staff_dependents");
        $this->db->exec("DROP TABLE IF EXISTS staff_bank_info");
        $this->db->exec("DROP TABLE IF EXISTS staff_work_experience");
        $this->db->exec("DROP TABLE IF EXISTS staff_certifications");
        $this->db->exec("DROP TABLE IF EXISTS staff_qualifications");
        $this->db->exec("DROP TABLE IF EXISTS staff_employment_info");
        $this->db->exec("DROP TABLE IF EXISTS staff_emergency_contacts");
        $this->db->exec("DROP TABLE IF EXISTS staff_contact_info");
        $this->db->exec("DROP TABLE IF EXISTS staff_personal_info");
        $this->db->exec("DROP TABLE IF EXISTS validation_staff");
        $this->db->exec("DROP TABLE IF EXISTS departments");
        $this->db->exec("DROP TABLE IF EXISTS units");
        
        $this->db->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
};
