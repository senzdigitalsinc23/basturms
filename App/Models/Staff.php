<?php

namespace App\Models;

use Database\ORM\Model;

class Staff extends Model
{
    protected static string $table = 'staff';

    public int $id;
    public string $staff_id;
    public string $first_name;
    public string $last_name;
    public ?string $other_name;
    public string $email;
    public string $phone;
    public ?string $signature_id;
    public string $id_type;
    public string $id_no;
    public ?string $snnit_no;
    public string $date_of_joining;
    public string $status;
    public string $added_on;
    public string $added_by;
    public ?int $is_archived;

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'staff_id' => $this->staff_id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'other_name' => $this->other_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'signature_id' => $this->signature_id,
            'id_type' => $this->id_type,
            'id_no' => $this->id_no,
            'snnit_no' => $this->snnit_no,
            'date_of_joining' => $this->date_of_joining,
            'status' => $this->status,
            'added_on' => $this->added_on,
            'added_by' => $this->added_by,
            'is_archived' => $this->is_archived,
        ];
    }
}
