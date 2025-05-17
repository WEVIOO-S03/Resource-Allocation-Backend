<?php
// src/Controller/ProjectController.php
namespace App\Controller;

use App\Repository\ProjectRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\ResourceRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\OccupationRecord;
use Symfony\Component\HttpFoundation\Request;

#[Route('/api')]
class ProjectController extends AbstractController
{
    #[Route('/projects', name: 'api_projects', methods: ['GET'])]
    public function getProjects(ProjectRepository $projectRepository): JsonResponse
    {
        $projects = $projectRepository->findAll();
        $today = new \DateTime();

        $formattedProjects = [];
        foreach ($projects as $project) {
            $formattedProjects[] = [
                'id' => $project->getId(),
                'code' => $project->getCode(),
                'name' => $project->getName(),
                'requiredSkills' => $project->getRequiredSkills(),
                'resources' => array_map(function($resource) use ($today) {
                    return [
                        'id' => $resource->getId(),
                        'fullName' => $resource->getFullName(),
                        'position' => $resource->getPosition(),
                        'skills' => $resource->getSkills(),
                        'occupationRate' => $resource->getOccupationRateForWeek($today),  // Updated from getOccupationRateForDate
                        'avatar' => $resource->getAvatar(),
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
    public function getProject(ProjectRepository $projectRepository, EntityManagerInterface $entityManager, int $id): JsonResponse
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
            'resources' => array_map(function($resource) use ($project, $entityManager) {
                return [
                    'id' => $resource->getId(),
                    'fullName' => $resource->getFullName(),
                    'position' => $resource->getPosition(),
                    'skills' => $resource->getSkills(),
                    'occupationRate' => $this->getResourceOccupationRateForProject($entityManager, $resource->getId(), $project->getId()),
                    'pole' => $resource->getPole() ? [
                        'id' => $resource->getPole()->getId(),
                        'name' => $resource->getPole()->getName()
                    ] : null
                ];
            }, $project->getResources()->toArray())
        ];

        return $this->json($formattedProject);
    }

    private function getResourceOccupationRateForProject(EntityManagerInterface $entityManager, $resourceId, $projectId): int
    {
        $today = new \DateTime();
        $weekStart = clone $today;
        $weekStart->modify('monday this week');
        
        $qb = $entityManager->createQueryBuilder();
        $qb->select('o')
           ->from('App\Entity\OccupationRecord', 'o')
           ->where('o.resource = :resourceId')
           ->andWhere('o.project = :projectId')
           ->andWhere('o.weekStart = :weekStart') 
           ->setParameter('resourceId', $resourceId)
           ->setParameter('projectId', $projectId)
           ->setParameter('weekStart', $weekStart)
           ->setMaxResults(1);
        
        $record = $qb->getQuery()->getOneOrNullResult();
        
        return $record ? $record->getOccupationRate() : 0;
    }

    #[Route('/projects/{id}/resources/{resourceId}', name: 'api_project_add_resource', methods: ['POST'])]
    public function addResourceToProject(
        Request $request,
        int $id,
        int $resourceId,
        ProjectRepository $projectRepository,
        ResourceRepository $resourceRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        try {
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
            
            $dateString = $request->request->get('date');
            try {
                $date = $dateString ? new \DateTime($dateString) : new \DateTime();
            } catch (\Exception $e) {
                $date = new \DateTime();
            }

            error_log("Adding resource {$resourceId} to project {$id} for week starting " . $date->format('Y-m-d'));
            
            try {
                $record = new OccupationRecord();
                $record->setResource($resource);
                $record->setProject($project);
                $record->setWeekStart($date);  // Changed from setDate to setWeekStart
                $record->setOccupationRate(0); 
                $record->setUpdatedAt(new \DateTime());
                
                if ($this->getUser()) {
                    $record->setUpdatedBy($this->getUser());
                }
                
                $entityManager->persist($record);
                $entityManager->flush();
                
                error_log("Successfully created occupation record with ID: {$record->getId()}");
            } catch (\Exception $e) {
                error_log("Error creating occupation record: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                
                $entityManager->flush();
                
                return $this->json([
                    'message' => 'Resource added to project but failed to set initial occupation rate',
                    'error' => $e->getMessage()
                ], 201);
            }

            return $this->json([
                'message' => 'Resource added to project successfully',
                'initialOccupationRate' => 0,
                'weekStart' => $record->getWeekStart()->format('Y-m-d'),
                'weekEnd' => $record->getWeekEnd()->format('Y-m-d')
            ], 201);
        } catch (\Exception $e) {
            error_log('Error in addResourceToProject: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            return $this->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
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
    
        $qb = $entityManager->createQueryBuilder();
        $qb->delete('App\Entity\OccupationRecord', 'o')
           ->where('o.resource = :resourceId')
           ->andWhere('o.project = :projectId')
           ->setParameter('resourceId', $resourceId)
           ->setParameter('projectId', $id);
        
        $qb->getQuery()->execute();
        
        $project->removeResource($resource);
        $entityManager->flush();
    
        return $this->json(['message' => 'Resource removed from project successfully']);
    }
}