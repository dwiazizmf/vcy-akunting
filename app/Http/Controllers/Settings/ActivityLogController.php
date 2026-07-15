<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $perPage = (int) $request->input('per_page', 25);
        $date = $request->input('date');

        $query = Activity::with(['causer', 'subject'])->latest();

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('log_name', 'like', "%{$search}%")
                  ->orWhereHasMorph('causer', [\App\Models\User::class], function($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($date) {
            $query->whereDate('created_at', $date);
        }

        $paginator = $query->paginate($perPage)->withQueryString();

        $paginator->getCollection()->transform(function ($activity) {
            return [
                'id' => $activity->id,
                'log_name' => $activity->log_name,
                'description' => $activity->description,
                'event' => $activity->event,
                'created_at' => $activity->created_at->format('Y-m-d H:i:s'),
                'causer' => $activity->causer ? $activity->causer->name : 'System',
                'subject_type' => class_basename($activity->subject_type),
                'subject_id' => $activity->subject_id,
                'properties' => $activity->properties,
            ];
        });

        $pagination = [
            'total'       => $paginator->total(),
            'perPage'     => $paginator->perPage(),
            'currentPage' => $paginator->currentPage(),
            'lastPage'    => $paginator->lastPage(),
            'from'        => $paginator->firstItem() ?: 0,
            'to'          => $paginator->lastItem() ?: 0,
        ];

        return Inertia::render('Settings/ActivityLog/Index', [
            'logs' => $paginator->items(),
            'pagination' => $pagination,
            'filters' => [
                'search' => $search ?? '',
                'date' => $date ?? '',
            ]
        ]);
    }
}
