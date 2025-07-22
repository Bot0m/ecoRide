<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class DateTransformer implements DataTransformerInterface
{
    /**
     * Transforme un objet DateTime en chaîne pour l'affichage
     */
    public function transform($value): string
    {
        if (null === $value) {
            return '';
        }

        if (!$value instanceof \DateTime) {
            throw new TransformationFailedException('Expected a DateTime object.');
        }

        return $value->format('Y-m-d');
    }

    /**
     * Transforme une chaîne en objet DateTime
     */
    public function reverseTransform($value): ?\DateTime
    {
        if (empty($value)) {
            return null;
        }

        if (!is_string($value)) {
            throw new TransformationFailedException('Expected a string.');
        }

        try {
            $date = new \DateTime($value);
            return $date;
        } catch (\Exception $e) {
            throw new TransformationFailedException('Invalid date format.');
        }
    }
} 