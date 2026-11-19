<?php

namespace App\Http\Controllers\Pro;

use App\Enums\ServiceRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Requests\StoreMessageRequest;
use App\Http\Requests\Requests\StoreQuoteRequest;
use App\Http\Requests\Requests\UpdateRequestStatusRequest;
use App\Models\ProviderProfile;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestQuote;
use App\Notifications\NewQuoteNotification;
use App\Notifications\ProfessionalReplyNotification;
use App\Services\Notifications\NotificationDispatcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Demandes de devis reçues par un professionnel.
 */
class ServiceRequestController extends Controller
{
    /**
     * Transitions de statut autorisées pour le professionnel.
     *
     * @var array<string, list<string>>
     */
    private const TRANSITIONS = [
        'in_progress' => ['pending'],
        'refused' => ['pending', 'in_progress', 'quoted'],
        'completed' => ['accepted'],
    ];

    /**
     * Affiche les demandes reçues.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ServiceRequest::class);

        $profileIds = ProviderProfile::query()
            ->managedBy($request->user())
            ->pluck('id');

        $status = (string) $request->string('status');

        $requests = ServiceRequest::query()
            ->with(['customer', 'service', 'quotes'])
            ->whereIn('provider_profile_id', $profileIds)
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('pro/Requests', [
            'requests' => [
                'data' => collect($requests->items())->map(fn (ServiceRequest $item) => $this->summary($item))->all(),
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'total' => $requests->total(),
            ],
            'filters' => ['status' => $status],
            'statuses' => collect(ServiceRequestStatus::cases())
                ->map(fn (ServiceRequestStatus $status) => ['value' => $status->value, 'label' => $status->label()])
                ->values()
                ->all(),
        ]);
    }

    /**
     * Affiche le détail d'une demande reçue.
     */
    public function show(Request $request, ServiceRequest $serviceRequest): Response
    {
        $this->authorize('view', $serviceRequest);

        $serviceRequest->load([
            'customer',
            'service',
            'quotes',
            'messages.author',
            'attachments',
            'bookings',
        ]);

        return Inertia::render('pro/RequestShow', [
            'request' => [
                ...$this->summary($serviceRequest),
                'description' => $serviceRequest->description,
                'address' => $serviceRequest->address,
                'quartier' => $serviceRequest->quartier,
                'budget_min' => $serviceRequest->budget_min,
                'budget_max' => $serviceRequest->budget_max,
                'desired_date' => $serviceRequest->desired_date?->toDateString(),
                'preferred_time' => $serviceRequest->preferred_time,
                'created_at' => $serviceRequest->created_at?->toIso8601String(),
                'customer' => [
                    'id' => $serviceRequest->customer?->id,
                    'name' => $serviceRequest->customer?->name,
                    'email' => $serviceRequest->customer?->email,
                    'phone' => $serviceRequest->customer?->phone,
                ],
                'service' => $serviceRequest->service?->only(['id', 'name', 'formatted_price']),
                'quotes' => $serviceRequest->quotes->map(fn (ServiceRequestQuote $quote) => [
                    'id' => $quote->id,
                    'reference' => $quote->reference,
                    'message' => $quote->message,
                    'amount' => $quote->amount,
                    'duration_minutes' => $quote->duration_minutes,
                    'status' => $quote->status->value,
                    'status_label' => $quote->status->label(),
                    'status_tone' => $quote->status->tone(),
                    'valid_until' => $quote->valid_until?->toDateString(),
                    'created_at' => $quote->created_at?->toIso8601String(),
                ])->values()->all(),
                'messages' => $serviceRequest->messages->map(fn ($message) => [
                    'id' => $message->id,
                    'body' => $message->body,
                    'author' => $message->author?->name,
                    'is_mine' => $message->author_id === $request->user()->id,
                    'created_at' => $message->created_at?->toIso8601String(),
                ])->values()->all(),
                'attachments' => $serviceRequest->attachments->map(fn ($attachment) => [
                    'id' => $attachment->id,
                    'name' => $attachment->name,
                    'type' => $attachment->type,
                    'url' => $attachment->url,
                ])->values()->all(),
            ],
            'transitions' => self::TRANSITIONS,
            'canQuote' => $request->user()->can('quote', $serviceRequest),
            'canRespond' => $request->user()->can('respond', $serviceRequest),
            'canComplete' => $request->user()->can('complete', $serviceRequest),
        ]);
    }

    /**
     * Change le statut d'une demande.
     */
    public function updateStatus(UpdateRequestStatusRequest $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        $this->authorize('view', $serviceRequest);

        $status = $request->enum('status', ServiceRequestStatus::class);
        $allowed = self::TRANSITIONS[$status->value] ?? [];

        if (! in_array($serviceRequest->status->value, $allowed, true)) {
            return back()->with('error', 'Ce changement de statut n’est pas autorisé.');
        }

        // La clôture exige la permission dédiée ; les autres transitions
        // relèvent de la réponse à la demande.
        $this->authorize($status === ServiceRequestStatus::Completed ? 'complete' : 'respond', $serviceRequest);

        $serviceRequest->forceFill([
            'status' => $status,
            'responded_at' => $serviceRequest->responded_at ?? now(),
            'closed_at' => $status->isClosed() ? now() : null,
        ])->save();

        return back()->with('success', 'Le statut de la demande a été mis à jour.');
    }

    /**
     * Pose une question au client sur la demande.
     */
    public function storeMessage(StoreMessageRequest $request, ServiceRequest $serviceRequest, NotificationDispatcher $notifications): RedirectResponse
    {
        $this->authorize('respond', $serviceRequest);

        $serviceRequest->messages()->create([
            'author_id' => $request->user()->id,
            'body' => $request->validated('body'),
        ]);

        $serviceRequest->load(['customer', 'providerProfile']);

        if ($serviceRequest->customer !== null && $serviceRequest->providerProfile !== null) {
            $notifications->toUser(
                $serviceRequest->customer,
                new ProfessionalReplyNotification($serviceRequest, $serviceRequest->providerProfile, $request->validated('body')),
            );
        }

        return back()->with('success', 'Votre question a été envoyée au client.');
    }

    /**
     * Envoie un devis au client.
     */
    public function storeQuote(StoreQuoteRequest $request, ServiceRequest $serviceRequest, NotificationDispatcher $notifications): RedirectResponse
    {
        $this->authorize('quote', $serviceRequest);

        $validated = $request->validated();

        $quote = DB::transaction(function () use ($serviceRequest, $validated) {
            $quote = $serviceRequest->quotes()->create([
                'provider_profile_id' => $serviceRequest->provider_profile_id,
                'service_id' => $validated['service_id'] ?? $serviceRequest->service_id,
                'message' => $validated['message'],
                'amount' => $validated['amount'],
                'duration_minutes' => isset($validated['duration_minutes']) ? min((int) $validated['duration_minutes'], 1440) : null,
                'valid_until' => $validated['valid_until'] ?? null,
            ]);

            $serviceRequest->forceFill([
                'status' => ServiceRequestStatus::Quoted,
                'responded_at' => now(),
            ])->save();

            return $quote;
        });

        $serviceRequest->load(['customer', 'providerProfile']);

        if ($serviceRequest->customer !== null) {
            $notifications->toUser(
                $serviceRequest->customer,
                new NewQuoteNotification($serviceRequest, $quote),
            );
        }

        return back()->with('success', 'Votre devis a été envoyé au client.');
    }

    /**
     * Résume une demande pour les listes.
     *
     * @return array<string, mixed>
     */
    private function summary(ServiceRequest $serviceRequest): array
    {
        return [
            'id' => $serviceRequest->id,
            'reference' => $serviceRequest->reference,
            'title' => $serviceRequest->title,
            'status' => $serviceRequest->status->value,
            'status_label' => $serviceRequest->status->label(),
            'status_tone' => $serviceRequest->status->tone(),
            'city' => $serviceRequest->city,
            'commune' => $serviceRequest->commune,
            'desired_date' => $serviceRequest->desired_date?->toDateString(),
            'customer' => $serviceRequest->customer?->name,
            'quotes_count' => $serviceRequest->quotes->count(),
            'created_at' => $serviceRequest->created_at?->toIso8601String(),
        ];
    }
}
