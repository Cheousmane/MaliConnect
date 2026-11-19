<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valide une demande d'attribution de rôles.
 *
 * Seuls les rôles connus de l'énumération sont acceptés. Les rôles
 * d'administration ne peuvent être attribués que par un super administrateur.
 */
class UpdateUserRolesRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à effectuer cette requête.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Récupère les règles de validation applicables à la requête.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['required', 'string', Rule::enum(UserRole::class)],
        ];
    }

    /**
     * Prépare les données validées avant la mise à jour des rôles.
     *
     * @return array<string, mixed>
     */
    public function validated($key = null, $default = null): mixed
    {
        if ($key === 'roles') {
            $roles = (array) parent::validated('roles', []);

            // Un administrateur classique ne peut ni créer ni révoquer un rôle d'administration.
            if (! $this->user()?->isSuperAdmin()) {
                $roles = array_values(array_filter(
                    $roles,
                    fn (string $role) => UserRole::tryFrom($role)?->isAssignableByAdmin() ?? false,
                ));
            }

            return $roles;
        }

        return parent::validated($key, $default);
    }
}
