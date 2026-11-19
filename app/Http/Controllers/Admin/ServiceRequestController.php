<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ServiceRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateServiceRequestStatusRequest;
use App\Models\ServiceRequest;
use App\Services\Admin\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Supervision administrative des demandes de devis.
 */
class ServiceRequestController extends Controller
{
    /**
     * Affiche la liste filtrable des demandes.
     */
    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('service_requests.manage'), 403);

        $search = trim((string) $request->string('search'));
        $status = (string) $request->string('status');

        $paginator = ServiceRequest::query()
            ->with(['customer:id,name', 'providerProfile:id,business_name'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('reference', 'like', '%'.$search.'%')
                        ->orWhere('title', 'like', '%'.$search.'%')
                        ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', '%'.$search.'%'))
                        ->orWhereHas('providerProfile', fn ($profile) => $profile->where('business_name', 'like', '%'.$search.'%'));
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/ServiceRequests', [
            'requests' => [
                'data' => collect($paginator->items())->map(fn (ServiceRequest $serviceRequest) => $this->summarize($serviceRequest))->all(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'statuses' => $this->statuses(),
            'pendingCount' => ServiceRequest::query()->where('status', ServiceRequestStatus::Pending)->count(),
        ]);
    }

    /**
     * Affiche le détail d'une demande.
     */
    public function show(Request $request, ServiceRequest $serviceRequest): Response
    {
        abort_unless($request->user()->can('service_requests.manage'), 403);

        $serviceRequest->load([
            'customer:id,name,email,phone',
            'providerProfile:id,business_name,slug',
            'service:id,name',
            'category:id,name',
            'quotes' => fn ($query) => $query->with('providerProfile:id,business_name')->latest(),
            'messages' => fn ($query) => $query->with('author:id,name')->oldest(),
            'attachments',
            'bookings' => fn ($query) => $query->with('customer:id,name')->latest(),
        ]);

        return Inertia::render('admin/ServiceRequestDetail', [
            'serviceRequest' => [
                ...$this->summarize($serviceRequest),
                'description' => $serviceRequest->description,
                'address' => $serviceRequest->address,
                'city' => $serviceRequest->city,
                'commune' => $serviceRequest->commune,
                'quartier' => $serviceRequest->quartier,
                'budget_min' => (float) $serviceRequest->budget_min,
                'budget_max' => (float) $serviceRequest->budget_max,
                'desired_date' => $serviceRequest->desired_date?->toDateString(),
                'preferred_time' => $serviceRequest->preferred_time,
                'responded_at' => $serviceRequest->responded_at?->toIso8601String(),
                'closed_at' => $serviceRequest->closed_at?->toIso8601String(),
                'customer' => $serviceRequest->customer?->only(['id', 'name', 'email', 'phone']),
                'professional' => $serviceRequest->providerProfile?->only(['id', 'business_name', 'slug']),
                'service' => $serviceRequest->service?->name,
                'category' => $serviceRequest->category?->name,
                'quotes' => $serviceRequest->quotes->map(fn ($quote) => [
                    'id' => $quote->id,
                    'reference' => $quote->reference,
                    'amount' => (float) $quote->amount,
                    'duration_minutes' => $quote->duration_minutes,
                    'valid_until' => $quote->valid_until?->toIso8601String(),
                    'professional' => $quote->providerProfile?->business_name,
                    'created_at' => $quote->created_at?->toIso8601String(),
                ])->values()->all(),
                'messages' => $serviceRequest->messages->map(fn ($message) => [
                    'id' => $message->id,
                    'body' => $message->body,
                    'author' => $message->author?->name,
                    'created_at' => $message->created_at?->toIso8601String(),
                ])->values()->all(),
                'attachments' => $serviceRequest->attachments->map(fn ($attachment) => [
                    'id' => $attachment->id,
                    'name' => $attachment->name,
                    'type' => $attachment->type,
                    'url' => $attachment->url,
                ])->values()->all(),
                'bookings' => $serviceRequest->bookings->map(fn ($booking) => [
                    'id' => $booking->id,
                    'reference' => $booking->reference,
                    'status' => $booking->status->value,
                    'status_label' => $booking->status->label(),
                    'status_tone' => $booking->status->tone(),
                    'starts_at' => $booking->starts_at?->toIso8601String(),
                ])->values()->all(),
            ],
            'statuses' => $this->statuses(),
        ]);
    }

    /**
     * Change le statut administratif d'une demande.
     */
    public function updateStatus(
        UpdateServiceRequestStatusRequest $request,
        ServiceRequest $serviceRequest,
        AuditLogger $audit,
    ): RedirectResponse {
        abort_unless($request->user()->can('service_requests.manage'), 403);

        $status = $request->enum('status', ServiceRequestStatus::class);
        $previous = $serviceRequest->status;

        DB::transaction(function () use ($request, $serviceRequest, $status, $previous, $audit) {
            $attributes = ['status' => $status];

            if ($status === ServiceRequestStatus::Quoted && $serviceRequest->responded_at === null) {
                $attributes['responded_at'] = now();
            }

            if (in_array($status, [ServiceRequestStatus::Completed, ServiceRequestStatus::Refused, ServiceRequestStatus::Cancelled], true)) {
                $attributes['closed_at'] = $serviceRequest->closed_at ?? now();
            }

            $serviceRequest->forceFill($attributes)->save();

            $audit->log(
                'service_request.status.updated',
                sprintf(
                    'Demande %s passée de « %s » à « %s » par l’administration.',
                    $serviceRequest->reference,
                    $previous->label(),
                    $status->label(),
                ),
                $serviceRequest,
                [
                    'previous_status' => $previous->value,
                    'new_status' => $status->value,
                    'note' => $request->validated('note'),
                ],
                $request->user(),
                $request,
            );
        });

        return back()->with('success', 'Le statut de la demande a été mis à jour.');
    }

    /**
     * Résume une demande pour l'interface d'administration.
     *
     * @return array<string, mixed>
     */
    private function summarize(ServiceRequest $serviceRequest): array
    {
        return [
            'id' => $serviceRequest->id,
            'reference' => $serviceRequest->reference,
            'title' => $serviceRequest->title,
            'status' => $serviceRequest->status->value,
            'status_label' => $serviceRequest->status->label(),
            'status_tone' => $serviceRequest->status->tone(),
            'customer' => $serviceRequest->customer?->name,
            'professional' => $serviceRequest->providerProfile?->business_name,
            'city' => $serviceRequest->city,
            'commune' => $serviceRequest->commune,
            'created_at' => $serviceRequest->created_at?->toIso8601String(),
        ];
    }

    /**
     * Liste des statuts disponibles.
     *
     * @return list<array{value: string, label: string}>
     */
    private function statuses(): array
    {
        return collect(ServiceRequestStatus::cases())
            ->map(fn (ServiceRequestStatus $status) => ['value' => $status->value, 'label' => $status->label()])
            ->values()
            ->all();
    }
}
