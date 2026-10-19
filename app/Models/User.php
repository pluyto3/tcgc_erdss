<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $hidden = ['password_hash'];

    protected $with = ['role', 'employee'];

    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function context(): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'email' => $this->email, 'role' => $this->role?->name,
            'employee_id' => $this->employee_id, 'department_id' => $this->employee?->department_id];
    }
}
