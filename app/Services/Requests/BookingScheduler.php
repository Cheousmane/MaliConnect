<?php

namespace App\Services\Requests;

use App\Enums\BookingStatus;
use App\Enums\QuoteStatus;
use App\Enums\ServiceRequestStatus;
use App\Models\ProviderBooking;
use App\Models\ProviderProfile;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestQuote;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Planifie les réservations et garantit l'absence de double réservation.
 *
 * Les opérations critiques sont exécutées dans une transaction : le profil
 * est verrouillé le temps du contrôle, ce qui sérialise les réservations
 * concurrentes sur un même créneau.
 */
class BookingScheduler
{
    public function __construct(private readonly AvailabilityChecker $availability) {}

    /**
     * Réserve un créneau pour un professionnel.
     *
     * @param  array<string, mixed>  $data
     */
    public function book(ServiceRequest $request, array $data): ProviderBooking
    {
        return DB::transaction(function () use ($request, $data) {
            // Le verrou empêche deux réservations simultanées sur le même profil.
            $profile = ProviderProfile::whereKey($request->provider_profile_id)->lockForUpdate()->firstOrFail();

            $startsAt = Carbon::parse($data['starts_at']);
            $duration = (int) ($data['duration_minutes'] ?? 60);
            $endsAt = $startsAt->copy()->addMinutes($duration);

            $this->assertSlotIsFree($profile, $startsAt, $endsAt);
            $this->assertAvailabilityRules($profile, $startsAt, $endsAt);

            return ProviderBooking::forceCreate([
                'customer_id' => $request->customer_id,
                'provider_profile_id' => $profile->id,
                'service_request_id' => $request->id,
                'service_request_quote_id' => $data['quote_id'] ?? null,
                'service_id' => $data['service_id'] ?? $request->service_id,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'duration_minutes' => $duration,
                'amount' => $data['amount'] ?? null,
                'address' => $data['address'] ?? $request->address,
                'notes' => $data['notes'] ?? null,
                'status' => BookingStatus::Pending,
            ]);
        });
    }

    /**
     * Accepte un devis et réserve le créneau correspondant.
     */
    public function acceptQuote(ServiceRequest $request, ServiceRequestQuote $quote, array $data): ProviderBooking
    {
        return DB::transaction(function () use ($request, $quote, $data) {
            $quote->forceFill([
                'status' => QuoteStatus::Accepted,
                'responded_at' => now(),
            ])->save();

            // Les autres devis de la demande deviennent caducs.
            $request->quotes()
                ->whereKeyNot($quote->id)
                ->where('status', QuoteStatus::Pending)
                ->update(['status' => QuoteStatus::Rejected, 'responded_at' => now()]);

            $request->forceFill([
                'status' => ServiceRequestStatus::Accepted,
                'responded_at' => now(),
            ])->save();

            // Utiliser la durée du formulaire si fournie, sinon celle du devis,
            // plafonnée à 24h max pour éviter les créneaux aberrants.
            $duration = (int) ($data['duration_minutes'] ?? $quote->duration_minutes ?? 60);
            $duration = min($duration, 1440);
            if ($duration < 15) {
                $duration = 60;
            }

            return $this->book($request, [
                'quote_id' => $quote->id,
                'amount' => $quote->amount,
                'duration_minutes' => $duration,
                'service_id' => $quote->service_id,
                'starts_at' => $data['starts_at'],
            ]);
        });
    }

    /**
     * Vérifie qu'aucune réservation active ne chevauche le créneau.
     */
    private function assertSlotIsFree(ProviderProfile $profile, Carbon $startsAt, Carbon $endsAt): void
    {
        $conflict = ProviderBooking::query()
            ->where('provider_profile_id', $profile->id)
            ->whereIn('status', [BookingStatus::Pending->value, BookingStatus::Confirmed->value])
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->exists();

        if ($conflict) {
            throw ValidationException::withMessages([
                'starts_at' => 'Ce créneau est déjà réservé. Choisissez un autre horaire.',
            ]);
        }
    }

    /**
     * Vérifie que le créneau respecte les règles de disponibilité du professionnel.
     */
    private function assertAvailabilityRules(ProviderProfile $profile, Carbon $startsAt, Carbon $endsAt): void
    {
        if (! $this->availability->isAvailable($profile, $startsAt, $endsAt)) {
            throw ValidationException::withMessages([
                'starts_at' => 'Le professionnel n’est pas disponible sur ce créneau.',
            ]);
        }
    }
}
