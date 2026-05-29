<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    protected $table = 'vcy_users';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    public function companies()
    {
        return $this->belongsToMany(
            \App\Models\Settings\Company::class,
            'vcy_user_companies',
            'user_id',
            'company_id'
        );
    }

    public function roles(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        $relation = $this->morphToMany(
            config('permission.models.role'),
            'user',
            config('permission.table_names.model_has_roles'),
            'user_id',
            app(\Spatie\Permission\PermissionRegistrar::class)->pivotRole
        );

        if (! config('permission.teams')) {
            return $relation;
        }

        $teamsKey = config('permission.column_names.team_foreign_key');
        $relation->withPivot($teamsKey);
        $teamField = config('permission.table_names.roles').'.'.$teamsKey;

        return $relation->wherePivot($teamsKey, getPermissionsTeamId())
            ->where(fn ($q) => $q->whereNull($teamField)->orWhere($teamField, getPermissionsTeamId()));
    }

    public function permissions(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        $relation = $this->morphToMany(
            config('permission.models.permission'),
            'user',
            config('permission.table_names.model_has_permissions'),
            'user_id',
            app(\Spatie\Permission\PermissionRegistrar::class)->pivotPermission
        );

        if (! config('permission.teams')) {
            return $relation;
        }

        $teamsKey = config('permission.column_names.team_foreign_key');
        $relation->withPivot($teamsKey);

        return $relation->wherePivot($teamsKey, getPermissionsTeamId());
    }
}
