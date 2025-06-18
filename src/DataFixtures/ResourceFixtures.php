<?php

namespace App\DataFixtures;

use App\Entity\Resource;
use App\Entity\User;
use App\Entity\Project;
use App\Entity\Pole;
use App\Entity\OccupationRecord;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ResourceFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        
        $user = $this->getReference('user', User::class);
        echo "Found user: " . $user->getEmail() . "\n";

       
        $resources = [
            
            [
                'fullName' => 'Manel Askri',
                'pole' => 'pole_php_development',
                'skills' => ['PHP', 'Symfony', 'MySQL', 'API Development'],
                'position' => 'Senior PHP Developer',
                'occupationRate' => 80
            ],
            [
                'fullName' => 'PHP Developer 2',
                'pole' => 'pole_php_development',
                'skills' => ['PHP', 'Laravel', 'PostgreSQL', 'REST APIs'],
                'position' => 'PHP Developer',
                'occupationRate' => 70
            ],

            
            [
                'fullName' => 'Charlie Brown',
                'pole' => 'pole_frontend_development',
                'skills' => ['React', 'TypeScript', 'CSS', 'Redux'],
                'position' => 'Senior Frontend Developer',
                'occupationRate' => 75
            ],
            [
                'fullName' => 'Frontend Developer 2',
                'pole' => 'pole_frontend_development',
                'skills' => ['Vue.js', 'JavaScript', 'SCSS', 'Vuex'],
                'position' => 'Frontend Developer',
                'occupationRate' => 65
            ],

            
            [
                'fullName' => 'Alice Johnson',
                'pole' => 'pole_design',
                'skills' => ['UI/UX Design', 'Figma', 'Adobe XD', 'Prototyping'],
                'position' => 'Senior UI/UX Designer',
                'occupationRate' => 70
            ],
            [
                'fullName' => 'Designer 2',
                'pole' => 'pole_design',
                'skills' => ['UI Design', 'Figma', 'Adobe Photoshop', 'Wireframing'],
                'position' => 'UI Designer',
                'occupationRate' => 60
            ]
        ];

        foreach ($resources as $resourceData) {
            try {
                $pole = $this->getReference($resourceData['pole'], Pole::class);
                echo "Found pole: " . $pole->getName() . "\n";
                
                $resource = new Resource();
                $resource->setFullName($resourceData['fullName']);
                $resource->setPole($pole);
                $resource->setSkills($resourceData['skills']);
                $resource->setPosition($resourceData['position']);
                $resource->setProjectManager($user);
                
                $occupationRecord = new OccupationRecord();
                $occupationRecord->setResource($resource);
                $occupationRecord->setDate(new \DateTime());
                $occupationRecord->setOccupationRate($resourceData['occupationRate']);
                $occupationRecord->setUpdatedBy($user);
                $occupationRecord->setUpdatedAt(new \DateTime());
                
                $resource->addOccupationRecord($occupationRecord);
                
                $manager->persist($resource);
                echo "Created resource: " . $resourceData['fullName'] . "\n";
            } catch (\Exception $e) {
                echo "Error creating resource: " . $e->getMessage() . "\n";
            }
        }

        $manager->flush();
        echo "Flushed all resources to database\n";
    }

    public function getDependencies(): array
    {
        return [
            PoleFixtures::class,
            UserFixtures::class,
        ];
    }
}