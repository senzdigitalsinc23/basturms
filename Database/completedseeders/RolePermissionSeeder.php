<?php

namespace Database\Seeders;

use Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {

        $roles = ['admin','staff','teacher','student'];
        foreach ($roles as $role) {
            $this->execute("INSERT IGNORE INTO roles (name) VALUES (?)", [$role]);
        }

        $permissions = [
            'view_dashboard',
            'manage_users','manage_roles','manage_permissions',
            'view_students','create_students','edit_students','delete_students','import_students',
            'view_staff','create_staff','edit_staff','delete_staff','import_staff','export_staff',
            'view_courses','create_courses','edit_courses','delete_courses','assign_teachers',
            'view_exams','create_exams','edit_exams','delete_exams','enter_grades','edit_grades',
            'view_attendance','mark_attendance','edit_attendance','export_students',
            'view_finance','manage_finance','create_payment','view_payments','refund_payments',
            'send_notifications','view_notifications',
            'manage_settings'
        ];

        foreach ($permissions as $perm) {
            $this->execute("INSERT IGNORE INTO permissions (name) VALUES (?)", [$perm]);
        }

        // Assign ALL permissions to admin
        $this->execute("INSERT IGNORE INTO role_permission (role_id, permission_id) SELECT r.id, p.id FROM roles r, permissions p WHERE r.name='admin'");
    }
}
