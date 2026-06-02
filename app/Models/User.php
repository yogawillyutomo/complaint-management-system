<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'email_verified_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;
    use HasRoles;

    protected string $guard_name = 'web';
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function complaints()
    {
        return $this->hasMany(Complaint::class, 'reporter_id');
    }

    public function assignedComplaints()
    {
        return $this->hasMany(Complaint::class, 'assigned_to');
    }

    public function hasRoleCustom($role)
    {
        return $this->roles
            ->pluck('name')
            ->map(fn($r) => strtolower(trim($r)))
            ->contains(strtolower(trim($role)));
    }
}
