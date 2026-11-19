<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Journal des actions sensibles de l'administration.
 */
class AuditLogController extends Controller
{
    /**
     * Affiche le journal filtrable.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', AdminAuditLog::class);

        $search = trim((string) $request->string('search'));
        $action = (string) $request->string('action');

        $paginator = AdminAuditLog::query()
            ->with('actor:id,name')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('description', 'like', '%'.$search.'%')
                        ->orWhere('action', 'like', '%'.$search.'%')
                        ->orWhereHas('actor', fn ($actor) => $actor->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->when($action !== '', fn ($query) => $query->where('action', $action))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('admin/AuditLogs', [
            'logs' => [
                'data' => collect($paginator->items())->map(fn (AdminAuditLog $log) => [
                    'id' => $log->id,
                    'action' => $log->action,
                    'action_label' => $log->actionLabel(),
                    'description' => $log->description,
                    'actor' => $log->actor?->name,
                    'subject_type' => $log->subject_type !== null ? class_basename($log->subject_type) : null,
                    'subject_id' => $log->subject_id,
                    'ip_address' => $log->ip_address,
                    'created_at' => $log->created_at?->toIso8601String(),
                ])->all(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
            'filters' => [
                'search' => $search,
                'action' => $action,
            ],
            'actions' => AdminAuditLog::query()
                ->select('action')
                ->distinct()
                ->orderBy('action')
                ->pluck('action')
                ->values()
                ->all(),
        ]);
    }

    /**
     * Affiche une entrée du journal.
     */
    public function show(Request $request, AdminAuditLog $log): Response
    {
        $this->authorize('view', $log);

        $log->load(['actor:id,name,email', 'subject']);

        return Inertia::render('admin/AuditLogDetail', [
            'log' => [
                'id' => $log->id,
                'action' => $log->action,
                'action_label' => $log->actionLabel(),
                'description' => $log->description,
                'actor' => $log->actor?->only(['id', 'name', 'email']),
                'subject_type' => $log->subject_type !== null ? class_basename($log->subject_type) : null,
                'subject_id' => $log->subject_id,
                'properties' => $log->properties,
                'ip_address' => $log->ip_address,
                'created_at' => $log->created_at?->toIso8601String(),
            ],
        ]);
    }
}
