<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ModerateReportRequest;
use App\Models\Business;
use App\Models\ProviderProfile;
use App\Models\ProviderReview;
use App\Models\Report;
use App\Models\User;
use App\Services\Admin\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Modération administrative des signalements.
 */
class ReportController extends Controller
{
    /**
     * Affiche la file des signalements.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Report::class);

        $search = trim((string) $request->string('search'));
        $status = (string) $request->string('status');
        $reason = (string) $request->string('reason');

        $paginator = Report::query()
            ->with(['reporter:id,name', 'handledBy:id,name', 'reportable'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('description', 'like', '%'.$search.'%')
                        ->orWhereHas('reporter', fn ($reporter) => $reporter->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($reason !== '', fn ($query) => $query->where('reason', $reason))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/Reports', [
            'reports' => [
                'data' => collect($paginator->items())->map(fn (Report $report) => $this->summarize($report))->all(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
            'filters' => [
                'search' => $search,
                'status' => $status,
                'reason' => $reason,
            ],
            'statuses' => $this->statuses(),
            'reasons' => $this->reasons(),
            'openCount' => Report::query()->open()->count(),
        ]);
    }

    /**
     * Affiche le détail d'un signalement.
     */
    public function show(Request $request, Report $report): Response
    {
        $this->authorize('view', $report);

        $report->load(['reporter:id,name,email', 'handledBy:id,name', 'reportable']);

        return Inertia::render('admin/ReportDetail', [
            'report' => [
                ...$this->summarize($report),
                'description' => $report->description,
                'resolution_note' => $report->resolution_note,
                'handled_at' => $report->handled_at?->toIso8601String(),
                'handled_by' => $report->handledBy?->name,
                'reporter' => $report->reporter?->only(['id', 'name', 'email']),
            ],
            'statuses' => $this->statuses(),
            'reasons' => $this->reasons(),
        ]);
    }

    /**
     * Enregistre une décision de modération.
     */
    public function updateStatus(
        ModerateReportRequest $request,
        Report $report,
        AuditLogger $audit,
    ): RedirectResponse {
        $this->authorize('manage', $report);

        $status = ReportStatus::from($request->validated('status'));
        $previous = $report->status;

        DB::transaction(function () use ($request, $report, $status, $previous, $audit) {
            $report->forceFill([
                'status' => $status,
                'handled_by' => $request->user()->id,
                'handled_at' => now(),
                'resolution_note' => $request->validated('resolution_note'),
            ])->save();

            $audit->log(
                'report.status.updated',
                sprintf(
                    'Signalement #%d passé de « %s » à « %s ».',
                    $report->id,
                    $previous->label(),
                    $status->label(),
                ),
                $report,
                [
                    'previous_status' => $previous->value,
                    'new_status' => $status->value,
                    'resolution_note' => $request->validated('resolution_note'),
                ],
                $request->user(),
                $request,
            );
        });

        return back()->with('success', 'Le signalement a été traité.');
    }

    /**
     * Résume un signalement pour l'interface d'administration.
     *
     * @return array<string, mixed>
     */
    private function summarize(Report $report): array
    {
        return [
            'id' => $report->id,
            'reason' => $report->reason->value,
            'reason_label' => $report->reason->label(),
            'status' => $report->status->value,
            'status_label' => $report->status->label(),
            'status_tone' => $report->status->tone(),
            'reporter' => $report->reporter?->name,
            'target' => $this->target($report),
            'created_at' => $report->created_at?->toIso8601String(),
        ];
    }

    /**
     * Décrit la ressource signalée.
     *
     * @return array<string, mixed>
     */
    private function target(Report $report): array
    {
        $target = $report->reportable;

        return match (true) {
            $target instanceof ProviderProfile => [
                'type' => 'profile',
                'label' => $target->business_name,
                'meta' => $target->status->label(),
                'url' => route('admin.provider-profiles.show', $target->id),
            ],
            $target instanceof Business => [
                'type' => 'business',
                'label' => $target->name,
                'meta' => $target->status->label(),
                'url' => route('admin.businesses.show', $target->id),
            ],
            $target instanceof ProviderReview => [
                'type' => 'review',
                'label' => 'Avis #'.$target->id,
                'meta' => $target->status->label(),
                'url' => route('admin.reviews.index'),
            ],
            $target instanceof User => [
                'type' => 'user',
                'label' => $target->name,
                'meta' => $target->status->label(),
                'url' => route('admin.users.show', $target->id),
            ],
            default => [
                'type' => 'unknown',
                'label' => 'Ressource indisponible',
                'meta' => null,
                'url' => null,
            ],
        };
    }

    /**
     * Liste des statuts disponibles.
     *
     * @return list<array{value: string, label: string}>
     */
    private function statuses(): array
    {
        return collect(ReportStatus::cases())
            ->map(fn (ReportStatus $status) => ['value' => $status->value, 'label' => $status->label()])
            ->values()
            ->all();
    }

    /**
     * Liste des motifs disponibles.
     *
     * @return list<array{value: string, label: string}>
     */
    private function reasons(): array
    {
        return collect(ReportReason::cases())
            ->map(fn (ReportReason $reason) => ['value' => $reason->value, 'label' => $reason->label()])
            ->values()
            ->all();
    }
}
