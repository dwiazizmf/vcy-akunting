<?php

namespace App\Models\Settings;

use Spatie\Permission\Models\Permission as SpatiePermission;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Support\Config;

class Permission extends SpatiePermission
{
    public function users(): BelongsToMany
    {
        return $this->morphedByMany(
            getModelForGuard($this->attributes['guard_name'] ?? config('auth.defaults.guard')),
            'user',
            Config::modelHasPermissionsTable(),
            app(PermissionRegistrar::class)->pivotPermission,
            Config::morphKey()
        );
    }
}
