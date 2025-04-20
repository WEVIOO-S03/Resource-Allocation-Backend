<?php
// src/Controller/ProjectController.php
namespace App\Controller;

use App\Repository\ProjectRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\ResourceRepository;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/api')]
class ProjectController extends AbstractController
{
    #[Route('/projects', name: 'api_projects', methods: ['GET'])]
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

    #[Route('/projects/{id}', name: 'api_project', methods: ['GET'])]
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

    #[Route('/projects/{id}/resources/{resourceId}', name: 'api_project_add_resource', methods: ['POST'])]
    public function addResourceToProject(
        int $id,
        int $resourceId,
        ProjectRepository $projectRepository,
        ResourceRepository $resourceRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $project = $projectRepository->find($id);
        if (!$project) {
            return $this->json(['error' => 'Project not found'], 404);
        }

        $resource = $resourceRepository->find($resourceId);
        if (!$resource) {
            return $this->json(['error' => 'Resource not found'], 404);
        }

        if ($project->getResources()->contains($resource)) {
            return $this->json(['error' => 'Resource is already assigned to this project'], 400);
        }

        $project->addResource($resource);
        $entityManager->flush();

        return $this->json(['message' => 'Resource added to project successfully']);
    }

    #[Route('/projects/{id}/resources/{resourceId}', name: 'api_project_remove_resource', methods: ['DELETE'])]
    public function removeResourceFromProject(
        int $id,
        int $resourceId,
        ProjectRepository $projectRepository,
        ResourceRepository $resourceRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $project = $projectRepository->find($id);
        if (!$project) {
            return $this->json(['error' => 'Project not found'], 404);
        }

        $resource = $resourceRepository->find($resourceId);
        if (!$resource) {
            return $this->json(['error' => 'Resource not found'], 404);
        }

        if (!$project->getResources()->contains($resource)) {
            return $this->json(['error' => 'Resource is not assigned to this project'], 400);
        }

        $project->removeResource($resource);
        $entityManager->flush();

        return $this->json(['message' => 'Resource removed from project successfully']);
    }
}