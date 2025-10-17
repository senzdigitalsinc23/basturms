<?php

namespace App\DTOs;

class UserDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $userId,
        public readonly string $username,
        public readonly string $email,
        public readonly ?string $password,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
        public readonly string $status,
        public readonly ?string $isSuperAdmin,
        public readonly ?int $roleId,
        public readonly ?string $roleName
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? 0,
            userId: $data['user_id'] ?? '',
            username: $data['username'] ?? '',
            email: $data['email'] ?? '',
            password: $data['password'] ?? null,
            createdAt: $data['created_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null,
            status: $data['status'] ?? 'inactive',
            isSuperAdmin: $data['is_super_admin'] ?? null,
            roleId: $data['role_id'] ?? null,
            roleName: $data['role_name'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'username' => $this->username,
            'email' => $this->email,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'status' => $this->status,
            'is_super_admin' => $this->isSuperAdmin,
            'role_id' => $this->roleId,
            'role_name' => $this->roleName
        ];
    }

    public function toArrayWithoutPassword(): array
    {
        $data = $this->toArray();
        unset($data['password']);
        return $data;
    }
}
