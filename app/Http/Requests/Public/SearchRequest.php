<?php

namespace App\Http\Requests\Public;

use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valide les filtres de la recherche publique.
 *
 * Les paramètres sont exposés en français dans l'URL publique ; ils sont
 * traduits en filtres techniques par la méthode filters().
 */
class SearchRequest extends FormRequest
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
            'q' => ['nullable', 'string', 'max:120'],
            'categorie' => ['nullable', 'integer', Rule::exists(Category::class, 'id')],
            'sous-categorie' => ['nullable', 'integer', Rule::exists(Subcategory::class, 'id')],
            'commune' => ['nullable', 'string', 'max:100'],
            'quartier' => ['nullable', 'string', 'max:100'],
            'prix-min' => ['nullable', 'numeric', 'min:0', 'max:100000000'],
            'prix-max' => ['nullable', 'numeric', 'min:0', 'max:100000000', 'gte:prix-min'],
            'note-min' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'disponible' => ['nullable', 'boolean'],
            'tri' => ['nullable', 'string', Rule::in(['pertinence', 'note', 'recents'])],
        ];
    }

    /**
     * Traduit les paramètres publics en filtres techniques.
     *
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        $sort = match ((string) $this->input('tri')) {
            'note' => 'rating',
            'recents' => 'recent',
            default => 'relevance',
        };

        return [
            'q' => $this->input('q'),
            'category_id' => $this->input('categorie'),
            'subcategory_id' => $this->input('sous-categorie'),
            'commune' => $this->input('commune'),
            'quartier' => $this->input('quartier'),
            'price_min' => $this->input('prix-min'),
            'price_max' => $this->input('prix-max'),
            'rating_min' => $this->input('note-min'),
            'available' => $this->boolean('disponible'),
            'sort' => $sort,
        ];
    }
}
