<?php

namespace App\Controller;

use App\Entity\Resource;
use App\Entity\Pole;
use App\Entity\Project;
use App\Entity\OccupationRecord;
use App\Repository\ResourceRepository;
use App\Repository\PoleRepository;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api')]
class ResourceController extends AbstractController
{
    #[Route('/resources', name: 'api_resources_index', methods: ['GET'])]
    public function index(ResourceRepository $resourceRepository, Request $request): JsonResponse
    {
        $projectId = $request->query->get('projectId');
        
        if ($projectId) {
            $resources = $resourceRepository->findByProject($projectId);
            return $this->json($resources);
        }
        
        $resourcesByPole = $resourceRepository->findAllGroupedByPole();
        return $this->json($resourcesByPole);
    }
    
    #[Route('/resources/all', name: 'api_resources_all', methods: ['GET'])]
    public function getAllResources(ResourceRepository $resourceRepository): JsonResponse
    {
        $resources = $resourceRepository->findAll();
        
        $formattedResources = array_map(function($resource) {
            return [
                'id' => $resource->getId(),
                'fullName' => $resource->getFullName(),
                'position' => $resource->getPosition(),
                'occupationRate' => $resource->getOccupationRate(),
                'availabilityRate' => $resource->getAvailabilityRate(),
                'avatar' => $resource->getAvatar(),
                'pole' => $resource->getPole() ? [
                    'id' => $resource->getPole()->getId(),
                    'name' => $resource->getPole()->getName()
                ] : null,
                'skills' => $resource->getSkills(),
                'projectManager' => $resource->getProjectManager() ? [
                    'id' => $resource->getProjectManager()->getId(),
                    'name' => $resource->getProjectManager()->getFirstName() . ' ' . $resource->getProjectManager()->getLastName()
                ] : null
            ];
        }, $resources);
        
        return $this->json($formattedResources);
    }

    #[Route('/resources/occupation-records', name: 'api_resources_occupation_records', methods: ['GET'])]
        public function getOccupationRecords(
            Request $request,
            ResourceRepository $resourceRepository,
            EntityManagerInterface $entityManager
        ): JsonResponse {
            try {
                $startDate = $request->query->get('startDate');
                $endDate = $request->query->get('endDate');
                
                error_log("Fetching occupation records for: startDate = $startDate, endDate = $endDate");
                
                if (!$startDate || !$endDate) {
                    return $this->json(['error' => 'startDate and endDate parameters are required'], 400);
                }
                
                try {
                    $startDateTime = new \DateTime($startDate);
                    $endDateTime = new \DateTime($endDate);
                } catch (\Exception $e) {
                    error_log('Date parsing error: ' . $e->getMessage());
                    return $this->json(['error' => 'Invalid date format. Use YYYY-MM-DD'], 400);
                }
                
                try {
                    $qb = $entityManager->createQueryBuilder();
                    $count = $qb->select('COUNT(o)')
                    ->from(OccupationRecord::class, 'o')
                    ->getQuery()
                    ->getSingleScalarResult();
                    
                    error_log("Total occupation records in database: $count");
                } catch (\Exception $e) {
                    error_log('Database connection test failed: ' . $e->getMessage());
                    return $this->json(['error' => 'Database connection issue'], 500);
                }
                
                $qb = $entityManager->createQueryBuilder();
                $qb->select('o')
                ->from(OccupationRecord::class, 'o')
                ->leftJoin('o.resource', 'r')
                ->where('o.date >= :startDate')
                ->andWhere('o.date <= :endDate')
                ->setParameter('startDate', $startDateTime)
                ->setParameter('endDate', $endDateTime);
                
                try {
                    error_log('Generated DQL: ' . $qb->getDQL());
                    
                    error_log('Query parameters: ' . json_encode([
                        'startDate' => $startDateTime->format('Y-m-d'),
                        'endDate' => $endDateTime->format('Y-m-d')
                    ]));
                    
                    $records = $qb->getQuery()->getResult();
                    error_log('Number of records found: ' . count($records));
                } catch (\Exception $e) {
                    error_log('Query execution error: ' . $e->getMessage());
                    error_log('Stack trace: ' . $e->getTraceAsString());
                    return $this->json(['error' => 'Failed to execute query: ' . $e->getMessage()], 500);
                }
                
                $formattedRecords = [];
                foreach ($records as $record) {
                    try {
                        $resource = $record->getResource();
                        if (!$resource) {
                            error_log('Warning: OccupationRecord #' . $record->getId() . ' has no associated resource');
                            continue;
                        }
                        
                        $formattedRecords[] = [
                            'resourceId' => $resource->getId(),
                            'date' => $record->getDate()->format('Y-m-d'),
                            'occupationRate' => $record->getOccupationRate(),
                            'updatedAt' => $record->getUpdatedAt()->format('Y-m-d H:i:s'),
                            'updatedBy' => $record->getUpdatedBy() ? $record->getUpdatedBy()->getEmail() : null
                        ];
                    } catch (\Exception $e) {
                        error_log('Error formatting record ID ' . ($record->getId() ?? 'unknown') . ': ' . $e->getMessage());
                        continue;
                    }
                }
                
                return $this->json($formattedRecords);
            } catch (\Exception $e) {
                error_log('Unexpected error in getOccupationRecords: ' . $e->getMessage());
                error_log('Stack trace: ' . $e->getTraceAsString());
                return $this->json(['error' => 'An unexpected error occurred: ' . $e->getMessage()], 500);
            }
}
    
    #[Route('/resources/{id}', name: 'api_resources_show', methods: ['GET'])]
    public function show(Resource $resource): JsonResponse
    {
        return $this->json([
            'id' => $resource->getId(),
            'fullName' => $resource->getFullName(),
            'position' => $resource->getPosition(),
            'occupationRate' => $resource->getOccupationRate(),
            'availabilityRate' => $resource->getAvailabilityRate(),
            'pole' => $resource->getPole() ? [
                'id' => $resource->getPole()->getId(),
                'name' => $resource->getPole()->getName()
            ] : null,
            'skills' => $resource->getSkills(),
            'projectManager' => $resource->getProjectManager()->getFirstName() . ' ' . $resource->getProjectManager()->getLastName(),
            'projects' => $resource->getProjects()->map(fn($p) => [
                'id' => $p->getId(),
                'name' => $p->getName()
            ])->toArray()
        ]);
    }
    
    #[Route('/resources/{id}', name: 'api_resources_update', methods: ['PUT'])]
    public function update(
        Request $request,
        Resource $resource,
        EntityManagerInterface $entityManager,
        PoleRepository $poleRepository,
        ProjectRepository $projectRepository,
        ValidatorInterface $validator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        
        if (isset($data['fullName'])) {
            $resource->setFullName($data['fullName']);
        }
        
        if (isset($data['position'])) {
            $resource->setPosition($data['position']);
        }
        
        if (isset($data['occupationRate'])) {
            $resource->setOccupationRate($data['occupationRate']);
        }
        
        if (isset($data['avatar'])) {
            $resource->setAvatar($data['avatar']);
        }
        
        if (isset($data['skills']) && is_array($data['skills'])) {
            $resource->setSkills($data['skills']);
        }
 
        if (isset($data['poleId'])) {
            $pole = $poleRepository->find($data['poleId']);
            if (!$pole) {
                return $this->json(['error' => 'Pole not found'], 404);
            }
            $resource->setPole($pole);
        }
        
        if (isset($data['projectIds']) && is_array($data['projectIds'])) {
            foreach ($resource->getProjects() as $project) {
                $resource->removeProject($project);
            }
            
            foreach ($data['projectIds'] as $projectId) {
                $project = $projectRepository->find($projectId);
                if ($project) {
                    $resource->addProject($project);
                }
            }
            
            $resource->setProjectManager($this->getUser());
        }
        
        $errors = $validator->validate($resource);
        if (count($errors) > 0) {
            return $this->json(['errors' => $errors], 400);
        }
        
        $entityManager->flush();
        
        return $this->json([
            'id' => $resource->getId(),
            'fullName' => $resource->getFullName(),
            'position' => $resource->getPosition(),
            'occupationRate' => $resource->getOccupationRate(),
            'availabilityRate' => $resource->getAvailabilityRate(),
            'pole' => $resource->getPole() ? [
                'id' => $resource->getPole()->getId(),
                'name' => $resource->getPole()->getName()
            ] : null,
            'skills' => $resource->getSkills(),
            'projectManager' => $resource->getProjectManager()->getFirstName() . ' ' . $resource->getProjectManager()->getLastName(),
            'projects' => $resource->getProjects()->map(fn($p) => [
                'id' => $p->getId(),
                'name' => $p->getName()
            ])->toArray()
        ]);
    }
    
    #[Route('/resources/{id}', name: 'api_resources_delete', methods: ['DELETE'])]
    public function delete(Resource $resource, EntityManagerInterface $entityManager): JsonResponse
    {
        $entityManager->remove($resource);
        $entityManager->flush();
        
        return $this->json(null, 204);
    }
    
    #[Route('/resources/{id}/occupation', name: 'api_resources_update_occupation', methods: ['PATCH'])]
    public function updateOccupation(
        Request $request,
        Resource $resource,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['occupationRate'])) {
            return $this->json(['error' => 'Occupation rate is required'], 400);
        }
        
        $occupationRate = floatval($data['occupationRate']);
        if ($occupationRate < 0 || $occupationRate > 100) {
            return $this->json(['error' => 'Occupation rate must be between 0 and 100'], 400);
        }
        
        $record = new OccupationRecord();
        $record->setResource($resource);
        $record->setDate(new \DateTime());
        $record->setOccupationRate((int)$occupationRate);
        
        if ($this->getUser()) {
            $record->setUpdatedBy($this->getUser());
        }
        
        $record->setUpdatedAt(new \DateTime());
        
        $entityManager->persist($record);
        $entityManager->flush();
        
        return $this->json([
            'id' => $resource->getId(),
            'occupationRate' => $record->getOccupationRate(),
            'date' => $record->getDate()->format('Y-m-d')
        ]);
    }

    #[Route('/resources/grouped-by-pole', name: 'api_resources_grouped_by_pole')]
    public function getResourcesGroupedByPole(Request $request, ResourceRepository $resourceRepository): JsonResponse
    {
        try {
            $date = null;
            if ($request->query->has('date')) {
                try {
                    $date = new \DateTime($request->query->get('date'));
                } catch (\Exception $e) {
                    return $this->json(['error' => 'Invalid date format. Use YYYY-MM-DD'], 400);
                }
            }
            
            $resources = $resourceRepository->findAllGroupedByPole($date);
            return $this->json($resources);
        } catch (\Exception $e) {
            error_log('Error in getResourcesGroupedByPole: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            return $this->json(['error' => 'An error occurred while fetching resources'], 500);
        }
    }






#[Route('/resources/{id}/occupation-records', name: 'api_resources_update_occupation_record', methods: ['POST'])]
public function updateOccupationRecord(
    Request $request,
    Resource $resource,
    EntityManagerInterface $entityManager
): JsonResponse {
    try {
        $data = json_decode($request->getContent(), true);
        
        error_log('Incoming data: ' . json_encode($data));
        
        if (!isset($data['date']) || !isset($data['occupationRate'])) {
            return $this->json(['error' => 'Date and occupation rate are required'], 400);
        }
        
        try {
            $date = new \DateTime($data['date']);
        } catch (\Exception $e) {
            error_log('Date conversion error: ' . $e->getMessage());
            return $this->json(['error' => 'Invalid date format. Use YYYY-MM-DD'], 400);
        }
        
        $occupationRate = (int)$data['occupationRate'];
        
        if ($occupationRate < 0 || $occupationRate > 100) {
            return $this->json(['error' => 'Occupation rate must be between 0 and 100'], 400);
        }
        
        try {
            $record = $entityManager->getRepository(OccupationRecord::class)
                ->findOneBy(['resource' => $resource, 'date' => $date]);
                
            if (!$record) {
                $record = new OccupationRecord();
                $record->setResource($resource);
                $record->setDate($date);
            }
        } catch (\Exception $e) {
            error_log('Error finding existing record: ' . $e->getMessage());
            $record = new OccupationRecord();
            $record->setResource($resource);
            $record->setDate($date);
        }
        
        $record->setOccupationRate($occupationRate);
        $record->setUpdatedAt(new \DateTime());
        
        if ($this->getUser()) {
            $record->setUpdatedBy($this->getUser());
        }
        
        try {
            $entityManager->persist($record);
            $entityManager->flush();
        } catch (\Exception $e) {
            error_log('Error persisting record: ' . $e->getMessage());
            error_log('Entity state: ' . json_encode([
                'resourceId' => $resource->getId(),
                'date' => $date->format('Y-m-d'),
                'occupationRate' => $occupationRate
            ]));
            
            if (strpos($e->getMessage(), 'duplicate key') !== false) {
                try {
                    $existingRecord = $entityManager->getRepository(OccupationRecord::class)
                        ->findOneBy(['resource' => $resource, 'date' => $date]);
                    
                    if ($existingRecord) {
                        $existingRecord->setOccupationRate($occupationRate);
                        $existingRecord->setUpdatedAt(new \DateTime());
                        if ($this->getUser()) {
                            $existingRecord->setUpdatedBy($this->getUser());
                        }
                        $entityManager->flush();
                        
                        return $this->json([
                            'id' => $existingRecord->getId(),
                            'resourceId' => $resource->getId(),
                            'date' => $date->format('Y-m-d'),
                            'occupationRate' => $occupationRate,
                            'updatedAt' => $existingRecord->getUpdatedAt()->format('Y-m-d H:i:s'),
                            'updatedBy' => $existingRecord->getUpdatedBy() ? $existingRecord->getUpdatedBy()->getEmail() : null
                        ]);
                    }
                } catch (\Exception $updateError) {
                    error_log('Error updating existing record: ' . $updateError->getMessage());
                    return $this->json(['error' => 'Failed to update existing record'], 500);
                }
            }
            
            return $this->json(['error' => 'Failed to save occupation record: ' . $e->getMessage()], 500);
        }
        
        return $this->json([
            'id' => $record->getId(),
            'resourceId' => $resource->getId(),
            'date' => $date->format('Y-m-d'),
            'occupationRate' => $occupationRate,
            'updatedAt' => $record->getUpdatedAt()->format('Y-m-d H:i:s'),
            'updatedBy' => $record->getUpdatedBy() ? $record->getUpdatedBy()->getEmail() : null
        ]);
    } catch (\Exception $e) {
        error_log('Error in updateOccupationRecord: ' . $e->getMessage());
        error_log('Stack trace: ' . $e->getTraceAsString());
        return $this->json(['error' => 'An error occurred while updating the occupation record'], 500);
    }
}
}