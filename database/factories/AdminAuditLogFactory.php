<?php

namespace Database\Factories;

use App\Models\AdminAuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdminAuditLog>
 */
class AdminAuditLogFactory extends Factory
{
    protected $model = AdminAuditLog::class;

    /**
     * Définit l'état par défaut du modèle.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'action' => fake()->randomElement([
                'user.status.updated',
                'provider_profile.approved',
                'business.status.updated',
                'payment.status.updated',
            ]),
            'description' => 'Intervention professionnelle et soignée.',
            'subject_type' => null,
            'subject_id' => null,
            'properties' => [],
            'ip_address' => fake()->ipv4(),
        ];
    }
}
