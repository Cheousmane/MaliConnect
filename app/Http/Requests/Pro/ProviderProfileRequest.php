<?php

namespace App\Http\Requests\Pro;

use App\Models\Business;
use App\Models\Category;
use App\Models\Subcategory;
use App\Rules\MaliPhoneNumber;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Règles communes aux formulaires de profil professionnel.
 */
abstract class ProviderProfileRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à effectuer cette requête.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Règles de validation du profil professionnel.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function profileRules(): array
    {
        return [
            'business_id' => ['nullable', 'integer', Rule::exists(Business::class, 'id')],
            'business_name' => ['required', 'string', 'max:120'],
            'category_id' => ['required', 'integer', Rule::exists(Category::class, 'id')],
            'subcategory_id' => [
                'nullable',
                'integer',
                Rule::exists(Subcategory::class, 'id'),
                Rule::exists(Subcategory::class, 'id')->where('category_id', $this->input('category_id')),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'phone' => ['nullable', 'string', 'max:20', new MaliPhoneNumber],
            'whatsapp' => ['nullable', 'string', 'max:20', new MaliPhoneNumber],
            'email' => ['nullable', 'string', 'email', 'max:255', Rule::unique('provider_profiles', 'email')->ignore($this->route('profile'))],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'commune' => ['nullable', 'string', 'max:100'],
            'quartier' => ['nullable', 'string', 'max:100'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'years_experience' => ['nullable', 'integer', 'between:0,80'],
            'response_time_hours' => ['nullable', 'integer', 'between:0,168'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }
}
