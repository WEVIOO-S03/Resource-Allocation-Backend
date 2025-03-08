<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Project;

class ProjectFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $projects = [
            [
                'code' => 'PROJ001', 
                'name' => 'Web Platform Development', 
                'requiredSkills' => ['PHP', 'Symfony', 'JavaScript', 'HTML/CSS']
            ],
            [
                'code' => 'PROJ002', 
                'name' => 'Mobile App Initiative', 
                'requiredSkills' => ['React Native', 'JavaScript', 'UI/UX Design']
            ],
            [
                'code' => 'PROJ003', 
                'name' => 'Enterprise Resource Planning', 
                'requiredSkills' => ['PHP', 'Database Design', 'System Architecture']
            ],
            [
                'code' => 'PROJ004', 
                'name' => 'Data Analytics Dashboard', 
                'requiredSkills' => ['Data Science', 'SQL', 'Data Visualization']
            ],
            [
                'code' => 'PROJ005', 
                'name' => 'Cloud Migration', 
                'requiredSkills' => ['Cloud Services', 'DevOps', 'System Administration']
            ]
        ];

        foreach ($projects as $data) {
            $project = new Project();
            $project->setCode($data['code']);
            $project->setName($data['name']);
            $project->setRequiredSkills($data['requiredSkills']); 

            $manager->persist($project);
        }

        $manager->flush();
    }
}
