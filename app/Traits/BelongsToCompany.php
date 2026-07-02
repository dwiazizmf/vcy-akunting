<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use App\Models\Settings\Company;

trait BelongsToCompany
{
    /**
     * Boot the BelongsToCompany trait.
     * Automatically registers a global scope to filter queries by active company_id.
     */
    protected static function bootBelongsToCompany()
    {
        static::addGlobalScope('company', function (Builder $builder) {
            $companyId = session('company_id') ?: (Company::where('enabled', 1)->first()?->id ?? 0);
            
            if ($companyId !== 'all') {
                $table = $builder->getModel()->getTable();
                $builder->where("{$table}.company_id", $companyId);
            }
        });
    }

    /**
     * Get the company that owns the model.
     */
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
