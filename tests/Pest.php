<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Cas de test
|--------------------------------------------------------------------------
|
| La closure fournie à vos fonctions de test est toujours liée à une classe
| de cas de test PHPUnit spécifique. Par défaut, il s'agit de
| "PHPUnit\Framework\TestCase". Vous pouvez la modifier avec la fonction
| "pest()" pour lier une autre classe ou un autre trait.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Unit');

/*
|--------------------------------------------------------------------------
| Attentes
|--------------------------------------------------------------------------
|
| Lorsque vous écrivez des tests, vous devez souvent vérifier que des valeurs
| respectent certaines conditions. La fonction "expect()" donne accès à un
| ensemble de méthodes d'attente que vous pouvez utiliser pour vérifier
| différents cas. Vous pouvez étendre l'API des attentes à tout moment.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Fonctions
|--------------------------------------------------------------------------
|
| Pest est très puissant, mais vous pouvez avoir du code de test spécifique
| au projet que vous ne souhaitez pas répéter dans chaque fichier. Vous pouvez
| exposer ici des helpers sous forme de fonctions globales afin de réduire
| la quantité de code dans vos fichiers de test.
|
*/

function something()
{
    // ...
}
