<?php

namespace App\Service;

/**
 * ✅ Service créé pour tester et initialiser MongoDB dans le projet Symfony.
 * Ce service sera réutilisé plus tard dans l'US de gestion des avis.
 */

use MongoDB\Client;
use MongoDB\Collection;

class AvisManager
{
    private Collection $collection;

    public function __construct()
    {
        $client = new Client('mongodb://localhost:27017');
        $this->collection = $client->ecoRideNoSQL->avis;
    }

    public function ajouterAvis(array $data): void
    {
        $this->collection->insertOne($data);
    }

    public function getTousLesAvis(): array
    {
        return $this->collection->find()->toArray();
    }
}