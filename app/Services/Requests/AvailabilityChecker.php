<?php

namespace App\Services\Requests;

use App\Models\ProviderProfile;
use Illuminate\Support\Carbon;

/**
 * Vérifie les règles de disponibilité d'un professionnel.
 *
 * Deux règles s'appliquent, dans cet ordre :
 *
 * 1. les créneaux ponctuels déclarés par le professionnel font foi ;
 * 2. à défaut, les horaires hebdomadaires d'ouverture sont respectés.
 */
class AvailabilityChecker
{
    /**
     * Indique si un créneau est disponible pour un professionnel.
     */
    public function isAvailable(ProviderProfile $profile, Carbon $startsAt, Carbon $endsAt): bool
    {
        $slots = $profile->availabilities()->get();

        if ($slots->isNotEmpty()) {
            // Un créneau explicitement marqué indisponible bloque la période.
            $blocked = $slots->contains(function ($slot) use ($startsAt, $endsAt) {
                if ($slot->is_available) {
                    return false;
                }

                [$slotStart, $slotEnd] = $this->slotBounds($slot);

                return $startsAt < $slotEnd && $endsAt > $slotStart;
            });

            if ($blocked) {
                return false;
            }

            // Le créneau doit être entièrement couvert par une plage disponible.
            return $slots
                ->where('is_available', true)
                ->contains(function ($slot) use ($startsAt, $endsAt) {
                    [$slotStart, $slotEnd] = $this->slotBounds($slot);

                    return $startsAt >= $slotStart && $endsAt <= $slotEnd;
                });
        }

        $hours = $profile->hours()->where('day_of_week', $startsAt->dayOfWeek)->first();

        // Sans horaires déclarés, aucune restriction n'est appliquée.
        if ($hours === null) {
            return true;
        }

        if ($hours->is_closed || $hours->opens_at === null || $hours->closes_at === null) {
            return false;
        }

        $opensAt = Carbon::parse($startsAt->format('Y-m-d').' '.$hours->opens_at);
        $closesAt = Carbon::parse($startsAt->format('Y-m-d').' '.$hours->closes_at);

        return $startsAt >= $opensAt && $endsAt <= $closesAt;
    }

    /**
     * Bornes d'une disponibilité ponctuelle pour le jour concerné.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function slotBounds(object $slot): array
    {
        $day = $slot->date->format('Y-m-d');

        return [
            Carbon::parse($day.' '.$slot->starts_at),
            Carbon::parse($day.' '.$slot->ends_at),
        ];
    }
}
