<?php

namespace App\Helpers;

use Illuminate\Support\Collection;
use App\Models\Accounting\Accounting\Account;

class AccountHelper
{
    /**
     * Format a collection of accounts into a hierarchical list for dropdowns.
     * Parents will have is_parent = true, children will be sorted under their respective parents.
     * 
     * @param Collection $accounts
     * @return array
     */
    public static function formatAccountsList($accounts)
    {
        $formatted = [];
        $parentIds = $accounts->pluck('id')->toArray();
        
        // Identify parents (either parent_id is null, or parent is not in the current filtered list)
        $parents = $accounts->filter(function($account) use ($parentIds) {
            return is_null($account->parent_id) || !in_array($account->parent_id, $parentIds);
        });

        foreach ($parents as $parent) {
            $formatted[] = [
                'id' => $parent->id,
                'code' => $parent->code,
                'name' => $parent->name,
                'is_parent' => true,
                'parent_id' => $parent->parent_id,
            ];
            
            // Find children of this parent
            $children = $accounts->filter(function($account) use ($parent) {
                return $account->parent_id === $parent->id;
            });

            foreach ($children as $child) {
                $formatted[] = [
                    'id' => $child->id,
                    'code' => $child->code,
                    'name' => $child->name,
                    'is_parent' => false,
                    'parent_id' => $child->parent_id,
                ];
            }
        }

        return $formatted;
    }

    /**
     * Get all active accounts for a company formatted for dropdowns.
     * 
     * @param int|null $companyId
     * @param string|null $typeCategory filter by type category (e.g. 'Revenue')
     * @return array
     */
    public static function getFormattedAccounts($companyId = null, $typeCategory = null)
    {
        $companyId = $companyId ?: session('company_id', 1);

        $query = Account::where('company_id', $companyId)
            ->where('enabled', 1)
            ->orderBy('code');
            
        if ($typeCategory) {
            $query->whereHas('type', function($q) use ($typeCategory) {
                $q->where('category', $typeCategory);
            });
        }
        
        $accounts = $query->get(['id', 'code', 'name', 'parent_id']);
        
        return self::formatAccountsList($accounts);
    }
}
