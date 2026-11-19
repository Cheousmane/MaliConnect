<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Catalogue initial des catégories et sous-catégories de MaliConnect.
 *
 * Les catégories sont hiérarchiques : une catégorie de premier niveau porte
 * des sous-catégories de second niveau.
 */
class CategorySeeder extends Seeder
{
    /**
     * Exécute le seeder.
     */
    public function run(): void
    {
        $categories = $this->catalogue();

        foreach ($categories as $index => $data) {
            $category = Category::updateOrCreate(
                ['slug' => Str::slug($data['name'])],
                [
                    'name' => $data['name'],
                    'slug' => Str::slug($data['name']),
                    'icon' => $data['icon'],
                    'color' => $data['color'],
                    'description' => $data['description'],
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ],
            );

            foreach ($data['subcategories'] as $subIndex => $subName) {
                Subcategory::updateOrCreate(
                    ['slug' => Str::slug($subName)],
                    [
                        'category_id' => $category->id,
                        'name' => $subName,
                        'slug' => Str::slug($subName),
                        'is_active' => true,
                        'sort_order' => $subIndex + 1,
                    ],
                );
            }
        }

        // Les catégories qui ne font plus partie du catalogue initial restent
        // en base (données historiques) mais ne sont plus proposées.
        $slugs = collect($categories)->map(fn (array $data) => Str::slug($data['name']));

        Category::query()->whereNotIn('slug', $slugs)->update(['is_active' => false]);

        // Idem pour les sous-catégories : celles qui ne sont plus au catalogue
        // sont désactivées, sans être supprimées.
        $subcategorySlugs = collect($categories)
            ->flatMap(fn (array $data) => $data['subcategories'])
            ->map(fn (string $name) => Str::slug($name));

        Subcategory::query()->whereNotIn('slug', $subcategorySlugs)->update(['is_active' => false]);

        $this->command->info('✅ '.Category::query()->where('is_active', true)->count().' catégories et '.Subcategory::query()->where('is_active', true)->count().' sous-catégories actives');
    }

    /**
     * Catalogue initial : cinq catégories et leurs sous-catégories.
     *
     * @return list<array{name: string, icon: string, color: string, description: string, subcategories: list<string>}>
     */
    private function catalogue(): array
    {
        return [
            [
                'name' => 'Maison et habitat',
                'icon' => 'home',
                'color' => '#3B82F6',
                'description' => 'Travaux, entretien et amélioration de votre logement',
                'subcategories' => [
                    'Plomberie',
                    'Électricité',
                    'Menuiserie',
                    'Maçonnerie et BTP',
                    'Peinture et décoration',
                    'Nettoyage du domicile',
                    'Jardinage et espaces verts',
                    'Chauffage et climatisation',
                    'Piscine et bassins',
                    'Énergie solaire',
                ],
            ],
            [
                'name' => 'Informatique et technologie',
                'icon' => 'monitor',
                'color' => '#06B6D4',
                'description' => 'Dépannage, réseaux, sites web, design graphique et équipements numériques',
                'subcategories' => [
                    'Dépannage informatique',
                    'Réparation de téléphone',
                    'Réseau et Wi-Fi',
                    'Site web et e-commerce',
                    'Câblage et installation',
                    'Sécurité et vidéosurveillance',
                    'Maintenance informatique',
                    'Impression et bureautique',
                    'Infographie et design graphique',
                    'Branding et identité visuelle',
                    'Logo et charte graphique',
                    'Supports de communication',
                    'Retouche photo et montage',
                    'Création de contenu et réseaux sociaux',
                ],
            ],
            [
                'name' => 'Transport et automobile',
                'icon' => 'car',
                'color' => '#10B981',
                'description' => 'Entretien et réparation des véhicules et des motos, déplacements et livraisons',
                'subcategories' => [
                    'Mécanique auto',
                    'Mécanique moto',
                    'Diagnostic auto',
                    'Vidange et entretien',
                    'Freinage',
                    'Pneumatique et équilibrage',
                    'Électricité auto',
                    'Tôlerie et peinture',
                    'Alignement des roues',
                    'Taxi et transport de personnes',
                    'Livraison et courses',
                    'Déménagement',
                    'Remorquage et dépannage',
                    'Location de véhicule',
                ],
            ],
            [
                'name' => 'Services personnels',
                'icon' => 'scissors',
                'color' => '#EC4899',
                'description' => 'Beauté, bien-être, aide à domicile et cours particuliers',
                'subcategories' => [
                    'Coiffure',
                    'Beauté et esthétique',
                    'Massage et bien-être',
                    'Ménage à domicile',
                    'Garde d’enfants',
                    'Cours particuliers',
                    'Soutien scolaire',
                    'Accompagnement et santé à domicile',
                    'Restauration et traiteur',
                    'Événementiel',
                ],
            ],
            [
                'name' => 'Professionnels et entreprises',
                'icon' => 'briefcase',
                'color' => '#6366F1',
                'description' => 'Services dédiés aux entreprises et aux particuliers',
                'subcategories' => [
                    'Comptabilité et gestion',
                    'Droit et juridique',
                    'Conseil et stratégie',
                    'Traduction et interpretation',
                    'Recrutement',
                    'Nettoyage de bureaux',
                    'Formation en entreprise',
                    'Marketing et communication',
                    'Audit et conformité',
                ],
            ],
        ];
    }
}
