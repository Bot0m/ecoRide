<?php

namespace App\Service;

use MongoDB\Client;
use MongoDB\BSON\UTCDateTime;
class ContactMessageService
{
    private $collection;

    public function __construct()
    {
        $client = new Client('mongodb://localhost:27017'); // adapte si nécessaire
        $db = $client->selectDatabase('ecoride');
        $this->collection = $db->selectCollection('contact_messages');
    }

    public function save(string $email, string $sujet, string $message): void
    {
        $this->collection->insertOne([
            'email' => $email,
            'sujet' => $sujet,
            'message' => $message,
            // @phpstan-ignore-next-line - UTCDateTime is from MongoDB extension
            'createdAt' => new \MongoDB\BSON\UTCDateTime()
        ]);
    }
}