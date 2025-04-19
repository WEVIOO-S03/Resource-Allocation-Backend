<?php
// src/Controller/ProjectController.php
namespace App\Controller;

use App\Repository\ProjectRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class ProjectController extends AbstractController
{
    #[Route('/api/projects', name: 'api_projects', methods: ['GET'])]
    public function getProjects(ProjectRepository $projectRepository): JsonResponse
    {
        $projects = $projectRepository->findAll();

        $formattedProjects = [];
        foreach ($projects as $project) {
            $formattedProjects[] = [
                'id' => $project->getId(),
                'code' => $project->getCode(),
                'name' => $project->getName(),
                'requiredSkills' => $project->getRequiredSkills(),
                'resources' => array_map(function($resource) {
                    return [
                        'id' => $resource->getId(),
                        'fullName' => $resource->getFullName(),
                        'position' => $resource->getPosition(),
                        'skills' => $resource->getSkills(),
                        'occupationRate' => $resource->getOccupationRate(),
                        'pole' => $resource->getPole() ? [
                            'id' => $resource->getPole()->getId(),
                            'name' => $resource->getPole()->getName()
                        ] : null
                    ];
                }, $project->getResources()->toArray())
            ];
        }

        return $this->json($formattedProjects);
    }

    #[Route('/api/projects/{id}', name: 'api_project', methods: ['GET'])]
    public function getProject(ProjectRepository $projectRepository, int $id): JsonResponse
    {
        $project = $projectRepository->find($id);

        if (!$project) {
            return $this->json(['error' => 'Project not found'], 404);
        }

        $formattedProject = [
            'id' => $project->getId(),
            'code' => $project->getCode(),
            'name' => $project->getName(),
            'requiredSkills' => $project->getRequiredSkills(),
            'resources' => array_map(function($resource) {
                return [
                    'id' => $resource->getId(),
                    'fullName' => $resource->getFullName(),
                    'position' => $resource->getPosition(),
                    'skills' => $resource->getSkills(),
                    'occupationRate' => $resource->getOccupationRate(),
                    'pole' => $resource->getPole() ? [
                        'id' => $resource->getPole()->getId(),
                        'name' => $resource->getPole()->getName()
                    ] : null
                ];
            }, $project->getResources()->toArray())
        ];

        return $this->json($formattedProject);
    }
}