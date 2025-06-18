<?php

namespace App\DataFixtures;

use App\Entity\Pole;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class PoleFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        
        $user = $this->getReference('user', User::class);

        $poles = [
            [
                'name' => 'PHP Development',
                'reference' => 'pole_php_development'
            ],
            [
                'name' => 'Frontend Development',
                'reference' => 'pole_frontend_development'
            ],
            [
                'name' => 'Design',
                'reference' => 'pole_design'
            ]
        ];

        foreach ($poles as $poleData) {
            $pole = new Pole();
            $pole->setName($poleData['name']);
            $pole->setCreatedBy($user);
            
            $manager->persist($pole);
           
            $this->addReference($poleData['reference'], $pole, Pole::class);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }
} 