<?php

use Database\Migration;

class CreateRolesPermissionsTables20250812120000 extends Migration {
    public function up():void
    {
         /*$this->schema->create('roles', function ($table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        $this->schema->create('permissions', function ($table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        $this->schema->create('role_permission', function ($table) {
            $table->integer('role_id');
            $table->integer('permission_id');
            $table->primary(['role_id', 'permission_id']);
        });

        $this->schema->table('users', function ($table) {
            $table->integer('role_id')->nullable();
        });*/
    }

    public function down():void
    {
        /* $this->schema->drop('role_permission');
        $this->schema->drop('permissions');
        $this->schema->drop('roles');
        $this->schema->table('users', function ($table) {
            $table->dropColumn('role_id');
        }); */
    } 
}
