<?php

namespace App\Services\Reviews;

use App\Enums\ReviewStatus;
use App\Models\ProviderBooking;
use App\Models\ProviderProfile;
use App\Models\ProviderReview;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Gère le cycle de vie des avis clients.
 *
 * La création est verrouillée sur la réservation afin d'empêcher deux avis
 * concurrents sur une même prestation. Les agrégats du profil sont recalculés
 * exclusivement à partir des avis approuvés.
 */
class ReviewService
{
    /**
     * Dépose un avis sur une réservation terminée et éligible.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(ProviderBooking $booking, User $customer, array $data): ProviderReview
    {
        return DB::transaction(function () use ($booking, $customer, $data) {
            $booking = ProviderBooking::query()
                ->whereKey($booking->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $booking->isReviewableBy($customer)) {
                throw ValidationException::withMessages([
                    'booking' => 'Cette prestation ne peut pas être évaluée : elle doit être terminée et ne pas déjà avoir d’avis.',
                ]);
            }

            return ProviderReview::create([
                'provider_booking_id' => $booking->id,
                'customer_id' => $customer->id,
                'provider_profile_id' => $booking->provider_profile_id,
                'rating' => (int) $data['rating'],
                'comment' => $data['comment'],
                'status' => ReviewStatus::Pending,
            ]);
        });
    }

    /**
     * Approuve un avis et le rend public.
     */
    public function approve(ProviderReview $review): ProviderReview
    {
        return DB::transaction(function () use ($review) {
            $review->forceFill([
                'status' => ReviewStatus::Approved,
                'published_at' => $review->published_at ?? now(),
            ])->save();

            $this->refreshRating($review->provider_profile_id);

            return $review->refresh();
        });
    }

    /**
     * Refuse un avis : il reste privé et n'entre pas dans la note moyenne.
     */
    public function reject(ProviderReview $review): ProviderReview
    {
        return DB::transaction(function () use ($review) {
            $review->forceFill([
                'status' => ReviewStatus::Rejected,
                'published_at' => null,
            ])->save();

            $this->refreshRating($review->provider_profile_id);

            return $review->refresh();
        });
    }

    /**
     * Enregistre la réponse publique du professionnel.
     */
    public function reply(ProviderReview $review, string $reply): ProviderReview
    {
        $review->forceFill([
            'professional_reply' => $reply,
            'replied_at' => now(),
        ])->save();

        return $review->refresh();
    }

    /**
     * Recalcule la note moyenne et le nombre d'avis publiés d'un profil.
     */
    public function refreshRating(int $providerProfileId): void
    {
        $profile = ProviderProfile::query()->find($providerProfileId);

        if ($profile === null) {
            return;
        }

        $stats = ProviderReview::query()
            ->where('provider_profile_id', $providerProfileId)
            ->where('status', ReviewStatus::Approved)
            ->selectRaw('COUNT(*) as total, AVG(rating) as average')
            ->first();

        $profile->forceFill([
            'rating_average' => round((float) ($stats->average ?? 0), 2),
            'rating_count' => (int) ($stats->total ?? 0),
        ])->saveQuietly();
    }
}
