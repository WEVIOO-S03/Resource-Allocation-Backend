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
            public function index(
                ResourceRepository $resourceRepository, 
                Request $request,
                EntityManagerInterface $em
            ): JsonResponse {
                $date = $request->query->get('date') ? new \DateTime($request->query->get('date')) : new \DateTime();
                
                $weekStart = (clone $date)->modify('monday this week');
                $weekEnd = (clone $weekStart)->modify('+6 days'); 
                
                $resources = $resourceRepository->findAllWithOccupationRates($weekStart, $weekEnd);
                
                $result = [];
                foreach ($resources as $resource) {
                    $projectOccupations = $resource->getProjectOccupations();
                    $totalOccupation = $resource->getOccupationRate(); 
                    
                    $calculatedTotal = 0;
                    foreach ($projectOccupations as $projectData) {
                        $calculatedTotal += $projectData['rate'];
                    }
                    
                    $warning = null;
                    if ($calculatedTotal > 100) {
                        $warning = "Total project allocation exceeds 100% ($calculatedTotal%)";
                    }
                    
                    $result[] = [
                        'id' => $resource->getId(),
                        'fullName' => $resource->getFullName(),
                        'position' => $resource->getPosition(),
                        'skills' => $resource->getSkills(),
                        'avatar' => $resource->getAvatar(),
                        'pole' => $resource->getPole() ? [
                            'id' => $resource->getPole()->getId(),
                            'name' => $resource->getPole()->getName()
                        ] : null,
                        'projectManager' => $resource->getProjectManager() ? [
                            'id' => $resource->getProjectManager()->getId(),
                            'name' => $resource->getProjectManager()->getFirstName() . ' ' . $resource->getProjectManager()->getLastName()
                        ] : null,
                        'occupation' => [
                            'total' => $totalOccupation,
                            'rawTotal' => $calculatedTotal, 
                            'byProject' => is_array($projectOccupations) ? array_map(function ($projectData) {
                                return [
                                    'projectId' => $projectData['projectId'],
                                    'projectName' => $projectData['projectName'],
                                    'rate' => $projectData['rate']
                                ];
                            }, $projectOccupations) : [],
                        ],
                        'availability' => 100 - $totalOccupation,
                        'projects' => $resource->getProjects()->map(function($project) {
                            return [
                                'id' => $project->getId(),
                                'name' => $project->getName()
                            ];
                        })->toArray(),
                        'weekStart' => $weekStart->format('Y-m-d'),
                        'weekEnd' => $weekEnd->format('Y-m-d')
                    ];
                    
                    if ($warning) {
                        $result[count($result) - 1]['warning'] = $warning;
                    }
                }
                
                return $this->json($result);
            }
    


            #[Route('/resources/occupation-records', name: 'api_resources_occupation_records', methods: ['GET'])]
            public function getOccupationRecords(
                Request $request,
                EntityManagerInterface $entityManager
            ): JsonResponse {
                try {
                    $startDate = $request->query->get('startDate');
                    $endDate = $request->query->get('endDate');
                    $projectId = $request->query->get('projectId');
                    
                    if (!$startDate || !$endDate) {
                        return $this->json(['error' => 'Both startDate and endDate are required'], 400);
                    }
                    
                    try {
                        $startDateTime = new \DateTime($startDate);
                        $endDateTime = new \DateTime($endDate);
                    } catch (\Exception $e) {
                        return $this->json(['error' => 'Invalid date format. Use YYYY-MM-DD'], 400);
                    }
                    
                    $weekStarts = [];
                    $currentWeekStart = (clone $startDateTime)->modify('monday this week');
                    
                    while ($currentWeekStart <= $endDateTime) {
                        $weekStarts[] = clone $currentWeekStart;
                        $currentWeekStart->modify('+7 days');
                    }
                    
                    $qb = $entityManager->createQueryBuilder();
                    $qb->select('o')
                        ->from('App\Entity\OccupationRecord', 'o')
                        ->where('o.weekStart >= :startDate')
                        ->andWhere('o.weekStart <= :endDate')
                        ->setParameter('startDate', $startDateTime)
                        ->setParameter('endDate', $endDateTime);
                    
                    if ($projectId) {
                        $qb->andWhere('o.project = :projectId')
                            ->setParameter('projectId', $projectId);
                    }
                    
                    $records = $qb->getQuery()->getResult();
                    
                    $result = [];
                    foreach ($records as $record) {
                        $result[] = [
                            'id' => $record->getId(),
                            'resourceId' => $record->getResource()->getId(),
                            'projectId' => $record->getProject() ? $record->getProject()->getId() : null,
                            'projectName' => $record->getProject() ? $record->getProject()->getName() : null,
                            'weekStart' => $record->getWeekStart()->format('Y-m-d'),
                            'weekEnd' => $record->getWeekEnd()->format('Y-m-d'),
                            'occupationRate' => $record->getOccupationRate(),
                            'updatedAt' => $record->getUpdatedAt() ? $record->getUpdatedAt()->format('Y-m-d H:i:s') : null,
                            'updatedBy' => $record->getUpdatedBy() ? $record->getUpdatedBy()->getEmail() : null
                        ];
                    }
                    
                    return $this->json($result);
                    
                } catch (\Exception $e) {
                    return $this->json(['error' => 'Failed to fetch occupation records: ' . $e->getMessage()], 500);
                }
            }
    
            #[Route('/resources/{id}', name: 'api_resources_show', methods: ['GET'])]
            public function show(Resource $resource, EntityManagerInterface $em, Request $request): JsonResponse
            {
                $date = $request->query->get('date') ? new \DateTime($request->query->get('date')) : new \DateTime();
                
                $weekStart = (clone $date)->modify('monday this week');
                $weekEnd = (clone $weekStart)->modify('+6 days');
                
                $qb = $em->createQueryBuilder();
                $occupationRecords = $qb->select('o, p')
                    ->from('App\Entity\OccupationRecord', 'o')
                    ->leftJoin('o.project', 'p')
                    ->where('o.resource = :resource')
                    ->andWhere('o.weekStart = :weekStart')
                    ->andWhere('o.weekEnd = :weekEnd')
                    ->setParameter('resource', $resource)
                    ->setParameter('weekStart', $weekStart)
                    ->setParameter('weekEnd', $weekEnd)
                    ->getQuery()
                    ->getResult();
                
                $projectOccupations = [];
                $rawTotal = 0;
                foreach ($occupationRecords as $record) {
                    $project = $record->getProject();
                    if (!$project) {
                        continue;
                    }
                    $rate = $record->getOccupationRate();
                    $rawTotal += $rate;
                    $projectOccupations[] = [
                        'projectId' => $project->getId(),
                        'projectName' => $project->getName(),
                        'rate' => $rate
                    ];
                }
                
                $totalOccupation = min(100, $rawTotal);
                $availability = 100 - $totalOccupation;
                
                $warning = null;
                if ($rawTotal > 100) {
                    $warning = "Total project allocation exceeds 100% ($rawTotal%)";
                }
                
                $data = [
                    'id' => $resource->getId(),
                    'fullName' => $resource->getFullName(),
                    'position' => $resource->getPosition(),
                    'skills' => $resource->getSkills(),
                    'avatar' => $resource->getAvatar(),
                    'pole' => $resource->getPole() ? [
                        'id' => $resource->getPole()->getId(),
                        'name' => $resource->getPole()->getName()
                    ] : null,
                    'projectManager' => $resource->getProjectManager() ? [
                        'id' => $resource->getProjectManager()->getId(),
                        'name' => $resource->getProjectManager()->getFirstName() . ' ' . $resource->getProjectManager()->getLastName()
                    ] : null,
                    'occupation' => [
                        'total' => $totalOccupation,
                        'rawTotal' => $rawTotal,
                        'byProject' => $projectOccupations,
                    ],
                    'availability' => $availability,
                    'projects' => $resource->getProjects()->map(fn($p) => [
                        'id' => $p->getId(),
                        'name' => $p->getName()
                    ])->toArray(),
                    'weekStart' => $weekStart->format('Y-m-d'),
                    'weekEnd' => $weekEnd->format('Y-m-d')
                ];
                
                if ($warning) {
                    $data['warning'] = $warning;
                }
                
                return $this->json($data);
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


            #[Route('/resources/{id}/projects/{projectId}/occupation-records', name: 'api_resources_update_occupation_record', methods: ['POST'])]
            public function updateOccupationRecord(
                Request $request,
                Resource $resource,
                int $projectId,
                ProjectRepository $projectRepository,
                EntityManagerInterface $entityManager
            ): JsonResponse {
                try {
                    $data = json_decode($request->getContent(), true);
                    
                    error_log('Incoming data: ' . json_encode($data));
                    
                    if (!isset($data['date']) || !isset($data['occupationRate'])) {
                        return $this->json(['error' => 'Date and occupation rate are required'], 400);
                    }
                    
                    $project = $projectRepository->find($projectId);
                    if (!$project) {
                        return $this->json(['error' => 'Project not found'], 404);
                    }
                    
                    try {
                        $date = new \DateTime($data['date']);
                        
                        $weekStart = (clone $date)->modify('monday this week');
                        $weekEnd = (clone $weekStart)->modify('+6 days'); 
                        
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
                            ->findOneBy([
                                'resource' => $resource, 
                                'project' => $project, 
                                'weekStart' => $weekStart,
                                'weekEnd' => $weekEnd
                            ]);
                            
                        if (!$record) {
                            $record = new OccupationRecord();
                            $record->setResource($resource);
                            $record->setProject($project);
                            $record->setWeekStart($weekStart);
                            $record->setWeekEnd($weekEnd);
                        }
                    } catch (\Exception $e) {
                        error_log('Error finding existing record: ' . $e->getMessage());
                        $record = new OccupationRecord();
                        $record->setResource($resource);
                        $record->setProject($project);
                        $record->setWeekStart($weekStart);
                        $record->setWeekEnd($weekEnd);
                    }
                    
                    $record->setOccupationRate($occupationRate);
                    $record->setUpdatedAt(new \DateTime());
                    
                    if ($this->getUser()) {
                        $record->setUpdatedBy($this->getUser());
                    }
                    
                    try {
                        $entityManager->persist($record);
                        $entityManager->flush();
                        
                        $totalOccupation = 0;
                        $allRecords = $entityManager->getRepository(OccupationRecord::class)
                            ->findBy([
                                'resource' => $resource,
                                'weekStart' => $weekStart,
                                'weekEnd' => $weekEnd
                            ]);
                            
                        foreach ($allRecords as $occRecord) {
                            $totalOccupation += $occRecord->getOccupationRate();
                        }
                        
                        if ($totalOccupation > 100) {
                            $warning = "Warning: Total occupation rate for this resource on this week exceeds 100% ({$totalOccupation}%)";
                        }
                        
                        $entityManager->refresh($resource);
                        
                        $projectRecords = [];
                        foreach ($allRecords as $rec) {
                            if ($rec->getProject()) {
                                $projectRecords[] = [
                                    'projectId' => $rec->getProject()->getId(),
                                    'projectName' => $rec->getProject()->getName(),
                                    'rate' => $rec->getOccupationRate()
                                ];
                            }
                        }
                        
                        $responseData = [
                            'id' => $record->getId(),
                            'resourceId' => $resource->getId(),
                            'projectId' => $project->getId(),
                            'projectName' => $project->getName(),
                            'date' => $date->format('Y-m-d'), 
                            'weekStart' => $weekStart->format('Y-m-d'),
                            'weekEnd' => $weekEnd->format('Y-m-d'),
                            'occupationRate' => $occupationRate,
                            'totalOccupation' => min(100, $totalOccupation),  
                            'projectOccupations' => $projectRecords,
                            'updatedAt' => $record->getUpdatedAt()->format('Y-m-d H:i:s'),
                            'updatedBy' => $record->getUpdatedBy() ? $record->getUpdatedBy()->getEmail() : null
                        ];
                        
                        if (isset($warning)) {
                            $responseData['warning'] = $warning;
                        }
                        
                        return $this->json($responseData);
                        
                    } catch (\Exception $e) {
                        error_log('Error persisting record: ' . $e->getMessage());
                        error_log('Entity state: ' . json_encode([
                            'resourceId' => $resource->getId(),
                            'projectId' => $project->getId(),
                            'weekStart' => $weekStart->format('Y-m-d'),
                            'weekEnd' => $weekEnd->format('Y-m-d'),
                            'occupationRate' => $occupationRate
                        ]));
                        
                        return $this->json(['error' => 'Failed to save occupation record: ' . $e->getMessage()], 500);
                    }
                } catch (\Exception $e) {
                    error_log('Error in updateOccupationRecord: ' . $e->getMessage());
                    error_log('Stack trace: ' . $e->getTraceAsString());
                    return $this->json(['error' => 'An error occurred while updating the occupation record'], 500);
                }
            }
}