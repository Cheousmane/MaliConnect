<?php

namespace App\Http\Controllers\Requests;

use App\Enums\QuoteStatus;
use App\Enums\ServiceRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Requests\RejectQuoteRequest;
use App\Http\Requests\Requests\StoreBookingRequest;
use App\Http\Requests\Requests\StoreMessageRequest;
use App\Http\Requests\Requests\StoreServiceRequestRequest;
use App\Models\PricingItem;
use App\Models\ProviderProfile;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestAttachment;
use App\Models\ServiceRequestQuote;
use App\Notifications\NewBookingNotification;
use App\Notifications\NewMessageNotification;
use App\Notifications\NewServiceRequestNotification;
use App\Notifications\QuoteAcceptedNotification;
use App\Notifications\QuoteRejectedNotification;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\Requests\BookingScheduler;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Demandes de devis et réservations du côté client.
 */
class ServiceRequestController extends Controller
{
    /**
     * Affiche les demandes et réservations du client.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $status = (string) $request->string('status');

        $requests = ServiceRequest::query()
            ->with(['providerProfile', 'quotes', 'bookings'])
            ->where('customer_id', $user->id)
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $bookings = $user->providerBookings()
            ->with(['providerProfile', 'service'])
            ->whereIn('status', ['pending', 'confirmed'])
            ->where('starts_at', '>=', now())
            ->orderBy('starts_at')
            ->limit(10)
            ->get();

        return Inertia::render('requests/Index', [
            'requests' => [
                'data' => collect($requests->items())->map(fn (ServiceRequest $item) => $this->summary($item))->all(),
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'total' => $requests->total(),
            ],
            'bookings' => $bookings->map(fn ($booking) => [
                'id' => $booking->id,
                'reference' => $booking->reference,
                'starts_at' => $booking->starts_at?->toIso8601String(),
                'status' => $booking->status->value,
                'status_label' => $booking->status->label(),
                'status_tone' => $booking->status->tone(),
                'professional' => $booking->providerProfile?->business_name,
                'service' => $booking->service?->name,
            ])->values()->all(),
            'filters' => ['status' => $status],
            'statuses' => collect(ServiceRequestStatus::cases())
                ->map(fn (ServiceRequestStatus $item) => ['value' => $item->value, 'label' => $item->label()])
                ->values()
                ->all(),
        ]);
    }

    /**
     * Affiche le formulaire de création d'une demande.
     */
    public function create(Request $request): Response
    {
        $this->authorize('create', ServiceRequest::class);

        $profile = null;

        if ($request->filled('profil')) {
            $profile = ProviderProfile::query()
                ->publiclyVisible()
                ->where('slug', $request->string('profil'))
                ->firstOrFail();
        }

        // Une prestation tarifée peut préremplir la demande de devis.
        $pricingItem = null;

        if ($request->filled('tarif')) {
            $pricingItem = PricingItem::query()
                ->whereKey($request->integer('tarif'))
                ->whereHas('pricingSheet', function ($query) use ($profile) {
                    $query->publiclyVisible()
                        ->when($profile !== null, fn ($sub) => $sub->where('provider_profile_id', $profile->id));
                })
                ->with(['pricingSheet.providerProfile', 'service'])
                ->first();

            if ($pricingItem !== null && $profile === null) {
                $profile = $pricingItem->pricingSheet?->providerProfile;

                if ($profile === null || ! $profile->isPubliclyVisible()) {
                    $pricingItem = null;
                    $profile = null;
                }
            }
        }

        $profile?->load([
            'category',
            'services' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order'),
        ]);

        $professionals = ProviderProfile::query()
            ->publiclyVisible()
            ->with([
                'category',
                'services' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order'),
            ])
            ->orderByDesc('rating_average')
            ->limit(50)
            ->get();

        // Le professionnel ciblé par un lien direct reste sélectionnable même
        // s'il ne figure pas parmi les profils les mieux notés.
        if ($profile !== null && ! $professionals->contains('id', $profile->id)) {
            $professionals->prepend($profile->load([
                'category',
                'services' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order'),
            ]));
        }

        return Inertia::render('requests/Create', [
            'profile' => $profile ? [
                'id' => $profile->id,
                'business_name' => $profile->business_name,
                'slug' => $profile->slug,
                'category' => $profile->category?->name,
                'city' => $profile->city,
                'commune' => $profile->commune,
                'services' => $profile->services->map(fn (Service $service) => $this->serviceSummary($service))->values()->all(),
            ] : null,
            'pricingItem' => $pricingItem ? [
                'id' => $pricingItem->id,
                'name' => $pricingItem->name,
                'service_id' => $pricingItem->service_id,
                'pricing_type' => $pricingItem->pricing_type->value,
                'pricing_type_label' => $pricingItem->pricing_type->label(),
                'formatted_price' => $pricingItem->formatted_price,
                'price_min' => $pricingItem->price_min,
                'price_max' => $pricingItem->price_max,
                'requires_diagnostic' => $pricingItem->requires_diagnostic,
                'sheet_name' => $pricingItem->pricingSheet?->name,
            ] : null,
            'professionals' => $professionals
                ->map(fn (ProviderProfile $item) => [
                    'id' => $item->id,
                    'business_name' => $item->business_name,
                    'category' => $item->category?->name,
                    'city' => $item->city,
                    'services' => $item->services->map(fn (Service $service) => $this->serviceSummary($service))->values()->all(),
                ])
                ->values()
                ->all(),
        ]);
    }

    /**
     * Enregistre une nouvelle demande de devis.
     */
    public function store(StoreServiceRequestRequest $request, NotificationDispatcher $notifications): RedirectResponse
    {
        $this->authorize('create', ServiceRequest::class);

        $validated = $request->validated();
        $profile = ProviderProfile::query()->publiclyVisible()->findOrFail($validated['provider_profile_id']);

        // La prestation tarifée doit appartenir à une fiche publiée du profil ciblé.
        $pricingItem = null;

        if (! empty($validated['pricing_item_id'])) {
            $pricingItem = PricingItem::query()
                ->whereKey($validated['pricing_item_id'])
                ->whereHas('pricingSheet', fn ($query) => $query
                    ->publiclyVisible()
                    ->where('provider_profile_id', $profile->id))
                ->firstOrFail();
        }

        $serviceRequest = DB::transaction(function () use ($validated, $request, $profile, $pricingItem) {
            $serviceRequest = ServiceRequest::create([
                'customer_id' => $request->user()->id,
                'provider_profile_id' => $profile->id,
                'service_id' => $validated['service_id'] ?? null,
                'pricing_item_id' => $pricingItem?->id,
                'category_id' => $profile->category_id,
                'title' => $validated['title'],
                'description' => $validated['description'],
                'address' => $validated['address'] ?? null,
                'city' => $validated['city'],
                'commune' => $validated['commune'] ?? null,
                'quartier' => $validated['quartier'] ?? null,
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'budget_min' => $validated['budget_min'] ?? null,
                'budget_max' => $validated['budget_max'] ?? null,
                'desired_date' => $validated['desired_date'] ?? null,
                'preferred_time' => $validated['preferred_time'] ?? null,
            ]);

            // Les pièces jointes sont conservées avec la demande.
            foreach ($request->file('attachments', []) as $file) {
                $path = $file->store('service-requests/'.$serviceRequest->id, 'local');

                $serviceRequest->attachments()->create([
                    'path' => $path,
                    'disk' => 'local',
                    'name' => $file->getClientOriginalName(),
                    'type' => str_contains((string) $file->getMimeType(), 'image') ? 'photo' : 'document',
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ]);
            }

            return $serviceRequest;
        });

        $serviceRequest->load('customer');

        // Tous les gestionnaires du profil sont avertis de la nouvelle demande.
        $notifications->toProfileManagers($profile, new NewServiceRequestNotification($serviceRequest));

        return to_route('requests.show', $serviceRequest)->with('success', 'Votre demande de devis a été envoyée.');
    }

    /**
     * Affiche le détail d'une demande de devis.
     */
    public function show(Request $request, ServiceRequest $serviceRequest): Response
    {
        $this->authorize('view', $serviceRequest);

        $serviceRequest->load([
            'providerProfile.category',
            'providerProfile.business',
            'service',
            'pricingItem',
            'quotes.providerProfile',
            'messages.author',
            'attachments',
            'bookings.review',
        ]);

        $booking = $serviceRequest->bookings->first();

        return Inertia::render('requests/Show', [
            'request' => [
                ...$this->summary($serviceRequest),
                'description' => $serviceRequest->description,
                'address' => $serviceRequest->address,
                'quartier' => $serviceRequest->quartier,
                'latitude' => $serviceRequest->latitude,
                'longitude' => $serviceRequest->longitude,
                'budget_min' => $serviceRequest->budget_min,
                'budget_max' => $serviceRequest->budget_max,
                'desired_date' => $serviceRequest->desired_date?->toDateString(),
                'preferred_time' => $serviceRequest->preferred_time,
                'professional' => $serviceRequest->providerProfile ? [
                    'id' => $serviceRequest->providerProfile->id,
                    'business_name' => $serviceRequest->providerProfile->business_name,
                    'slug' => $serviceRequest->providerProfile->slug,
                    'category' => $serviceRequest->providerProfile->category?->name,
                    'phone' => $serviceRequest->providerProfile->phone,
                ] : null,
                'service' => $serviceRequest->service?->only(['id', 'name', 'formatted_price']),
                'pricing_item' => $serviceRequest->pricingItem ? [
                    'id' => $serviceRequest->pricingItem->id,
                    'name' => $serviceRequest->pricingItem->name,
                    'formatted_price' => $serviceRequest->pricingItem->formatted_price,
                ] : null,
                'quotes' => $serviceRequest->quotes->map(fn (ServiceRequestQuote $quote) => [
                    'id' => $quote->id,
                    'reference' => $quote->reference,
                    'message' => $quote->message,
                    'amount' => $quote->amount,
                    'duration_minutes' => $quote->duration_minutes,
                    'status' => $quote->status->value,
                    'status_label' => $quote->status->label(),
                    'status_tone' => $quote->status->tone(),
                    'rejection_reason' => $quote->rejection_reason,
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
                'booking' => $booking ? [
                    'id' => $booking->id,
                    'reference' => $booking->reference,
                    'starts_at' => $booking->starts_at?->toIso8601String(),
                    'status' => $booking->status->value,
                    'status_label' => $booking->status->label(),
                    'status_tone' => $booking->status->tone(),
                    'review' => $booking->review ? [
                        'id' => $booking->review->id,
                        'status' => $booking->review->status->value,
                        'status_label' => $booking->review->status->label(),
                        'status_tone' => $booking->review->status->tone(),
                    ] : null,
                    'can_review' => $request->user()->can('review', $booking),
                ] : null,
            ],
            'canCancel' => $request->user()->can('cancel', $serviceRequest),
            'canMessage' => ! $serviceRequest->status->isClosed(),
            'canRejectQuote' => $request->user()->can('rejectQuote', $serviceRequest),
        ]);
    }

    /**
     * Télécharge une pièce jointe après vérification de l'accès à la demande.
     *
     * Les fichiers récents sont sur le disque privé « local ». Le repli sur
     * « public » permet de servir les pièces jointes créées avant la migration
     * sans les exposer directement.
     */
    public function downloadAttachment(ServiceRequest $serviceRequest, ServiceRequestAttachment $attachment): StreamedResponse
    {
        $this->authorize('view', $serviceRequest);

        abort_unless((int) $attachment->service_request_id === (int) $serviceRequest->id, 404);
        abort_if(str_contains($attachment->path, '..'), 404);

        foreach (array_filter(array_unique([$attachment->disk, 'local', 'public'])) as $disk) {
            $storage = Storage::disk($disk);

            if ($storage->exists($attachment->path)) {
                return $storage->download($attachment->path, $attachment->name);
            }
        }

        abort(404);
    }

    /**
     * Ajoute un message à la demande.
     */
    public function storeMessage(StoreMessageRequest $request, ServiceRequest $serviceRequest, NotificationDispatcher $notifications): RedirectResponse
    {
        $this->authorize('view', $serviceRequest);

        if ($serviceRequest->status->isClosed()) {
            return back()->with('error', 'Cette demande est close : aucun message ne peut être ajouté.');
        }

        $body = $request->validated('body');

        $serviceRequest->messages()->create([
            'author_id' => $request->user()->id,
            'body' => $body,
        ]);

        $serviceRequest->loadMissing(['customer', 'providerProfile']);
        if ($serviceRequest->providerProfile !== null) {
            $notifications->toProfileManagers(
                $serviceRequest->providerProfile,
                new NewMessageNotification($serviceRequest, $body),
            );
        }

        return back()->with('success', 'Votre message a été envoyé.');
    }

    /**
     * Annule la demande de devis.
     */
    public function cancel(Request $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        $this->authorize('cancel', $serviceRequest);

        $serviceRequest->forceFill([
            'status' => ServiceRequestStatus::Cancelled,
            'closed_at' => now(),
        ])->save();

        return back()->with('success', 'La demande a été annulée.');
    }

    /**
     * Accepte un devis et réserve le créneau correspondant.
     */
    public function acceptQuote(
        StoreBookingRequest $request,
        ServiceRequest $serviceRequest,
        ServiceRequestQuote $quote,
        BookingScheduler $scheduler,
        NotificationDispatcher $notifications,
    ): RedirectResponse {
        $this->authorize('acceptQuote', $serviceRequest);

        if ($quote->service_request_id !== $serviceRequest->id) {
            abort(404);
        }

        if ($quote->status !== QuoteStatus::Pending) {
            return back()->with('error', 'Ce devis n’est plus disponible.');
        }

        $data = $request->validated();

        $booking = $scheduler->acceptQuote($serviceRequest, $quote, $data);

        $serviceRequest->load('customer');
        $booking->load(['customer', 'service', 'providerProfile']);

        $profile = $serviceRequest->providerProfile;
        $notifications->toProfileManagers($profile, new QuoteAcceptedNotification($serviceRequest, $quote, $booking));
        $notifications->toProfileManagers($profile, new NewBookingNotification($booking));

        return to_route('requests.show', $serviceRequest)
            ->with('success', 'Devis accepté. La réservation '.$booking->reference.' est enregistrée.');
    }

    /**
     * Refuse un devis et prévient le professionnel.
     */
    public function rejectQuote(
        RejectQuoteRequest $request,
        ServiceRequest $serviceRequest,
        ServiceRequestQuote $quote,
        NotificationDispatcher $notifications,
    ): RedirectResponse {
        $this->authorize('rejectQuote', $serviceRequest);

        if ($quote->service_request_id !== $serviceRequest->id) {
            abort(404);
        }

        if ($quote->status !== QuoteStatus::Pending) {
            return back()->with('error', 'Ce devis n’est plus disponible.');
        }

        $reason = $request->validated('reason');

        DB::transaction(function () use ($serviceRequest, $quote, $reason) {
            $quote->forceFill([
                'status' => QuoteStatus::Rejected,
                'responded_at' => now(),
                'rejection_reason' => $reason,
            ])->save();

            // Sans autre devis en attente, la demande revient en cours de traitement.
            $hasPendingQuote = $serviceRequest->quotes()
                ->where('status', QuoteStatus::Pending)
                ->exists();

            if (! $hasPendingQuote && $serviceRequest->status === ServiceRequestStatus::Quoted) {
                $serviceRequest->forceFill(['status' => ServiceRequestStatus::InProgress])->save();
            }
        });

        $serviceRequest->load('customer');
        $notifications->toProfileManagers(
            $serviceRequest->providerProfile,
            new QuoteRejectedNotification($serviceRequest, $quote, $reason),
        );

        return back()->with('success', 'Le devis a été refusé.');
    }

    /**
     * Résume un service pour le formulaire de demande.
     *
     * @return array<string, mixed>
     */
    private function serviceSummary(Service $service): array
    {
        return [
            'id' => $service->id,
            'name' => $service->name,
            'price_type' => $service->price_type,
            'price_min' => $service->price_min,
            'formatted_price' => $service->formatted_price,
        ];
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
            'professional' => $serviceRequest->providerProfile?->business_name,
            'quotes_count' => $serviceRequest->quotes->count(),
            'created_at' => $serviceRequest->created_at?->toIso8601String(),
        ];
    }
}
