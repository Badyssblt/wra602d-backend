<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\City;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Bridge\Doctrine\Attribute\AsEntityListener;

#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: City::class)]
final class CityListener
{
    public function preUpdate(City $city, PreUpdateEventArgs $event): void
    {
        $city->setUpdatedAt(new \DateTimeImmutable());
    }
}
