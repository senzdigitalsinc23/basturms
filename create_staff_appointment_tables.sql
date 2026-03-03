-- Create staff_subjects table
CREATE TABLE IF NOT EXISTS staff_subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id VARCHAR(20) NOT NULL,
    subject_id VARCHAR(10) NOT NULL,
    class_id VARCHAR(10) NOT NULL,
    assigned_by VARCHAR(20) NOT NULL,
    assigned_on DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_staff_id (staff_id),
    INDEX idx_subject_id (subject_id),
    INDEX idx_class_id (class_id)
);

-- Create staff_appointment_history table
CREATE TABLE IF NOT EXISTS staff_appointment_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id VARCHAR(20) NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_status VARCHAR(20) NOT NULL DEFAULT 'appointed',
    class_teacher_for VARCHAR(10) NULL,
    created_by VARCHAR(20) NOT NULL,
    created_on DATETIME NOT NULL DEFAULT CURR