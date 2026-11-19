<?php

namespace Database\Factories;

use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Models\ProviderProfile;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Report>
 */
class ReportFactory extends Factory
{
    protected $model = Report::class;

    /**
     * Définit l'état par défaut du modèle.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reporter_id' => User::factory(),
            'reportable_type' => ProviderProfile::class,
            'reportable_id' => ProviderProfile::factory(),
            'reason' => fake()->randomElement(ReportReason::cases()),
            'description' => 'Prestation de qualité pour vos besoins à Bamako.',
            'status' => ReportStatus::Pending,
            'handled_by' => null,
            'handled_at' => null,
            'resolution_note' => null,
        ];
    }

    /**
     * Indique que le signalement est encore en attente.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReportStatus::Pending,
            'handled_by' => null,
            'handled_at' => null,
            'resolution_note' => null,
        ]);
    }

    /**
     * Indique que le signalement a été traité.
     */
    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReportStatus::Resolved,
            'handled_at' => now(),
            'resolution_note' => 'Intervention professionnelle et soignée.',
        ]);
    }

    /**
     * Indique que le signalement a été classé sans suite.
     */
    public function dismissed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReportStatus::Dismissed,
            'handled_at' => now(),
            'resolution_note' => 'Intervention professionnelle et soignée.',
        ]);
    }

    /**
     * Vise un profil professionnel donné.
     */
    public function forProfile(ProviderProfile $profile): static
    {
        return $this->state(fn (array $attributes) => [
            'reportable_type' => ProviderProfile::class,
            'reportable_id' => $profile->id,
        ]);
    }
}
