<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Models\ProviderBooking;
use App\Models\ProviderProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProviderBooking>
 */
class ProviderBookingFactory extends Factory
{
    protected $model = ProviderBooking::class;

    /**
     * Définit l'état par défaut du modèle.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = now()->addDays(2)->setTime(10, 0);

        return [
            'customer_id' => User::factory(),
            'provider_profile_id' => ProviderProfile::factory(),
            'service_request_id' => null,
            'service_request_quote_id' => null,
            'service_id' => null,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addHour(),
            'duration_minutes' => 60,
            'amount' => 15000,
            'address' => 'Bamako, Commune IV',
            'notes' => null,
        ];
    }

    /**
     * Applique le statut après l'assignation des attributs.
     *
     * Le statut n'est volontairement pas assignable en masse : il est piloté
     * par les transitions métier et non par les requêtes utilisateur.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (ProviderBooking $booking) {
            if ($booking->status === null) {
                $booking->forceFill(['status' => BookingStatus::Pending]);
            }
        });
    }

    /**
     * Indique que la prestation est terminée.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => now()->subDays(2),
            'ends_at' => now()->subDays(2)->addHour(),
        ])->afterMaking(fn (ProviderBooking $booking) => $booking->forceFill([
            'status' => BookingStatus::Completed,
            'completed_at' => now()->subDay(),
        ]));
    }

    /**
     * Indique que la réservation a été confirmée.
     */
    public function confirmed(): static
    {
        return $this->afterMaking(fn (ProviderBooking $booking) => $booking->forceFill([
            'status' => BookingStatus::Confirmed,
            'confirmed_at' => now(),
        ]));
    }

    /**
     * Indique que la réservation a été annulée.
     */
    public function cancelled(): static
    {
        return $this->afterMaking(fn (ProviderBooking $booking) => $booking->forceFill([
            'status' => BookingStatus::Cancelled,
            'cancelled_at' => now(),
            'cancelled_by' => 'customer',
        ]));
    }

    /**
     * Associe la réservation à un client existant.
     */
    public function forCustomer(User $customer): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_id' => $customer->id,
        ]);
    }

    /**
     * Associe la réservation à un profil existant.
     */
    public function forProfile(ProviderProfile $profile): static
    {
        return $this->state(fn (array $attributes) => [
            'provider_profile_id' => $profile->id,
        ]);
    }
}
