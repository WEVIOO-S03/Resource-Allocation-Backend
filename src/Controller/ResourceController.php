<?php

namespace App\Controller;

use App\Entity\Resource;
use App\Entity\Pole;
use App\Entity\Project;
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
    public function index(ResourceRepository $resourceRepository): JsonResponse
    {
        $resourcesByPole = $resourceRepository->findAllGroupedByPole();
        return $this->json($resourcesByPole);
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
        
        $resource->setOccupationRate($occupationRate);
        $entityManager->flush();
        
        return $this->json([
            'id' => $resource->getId(),
            'occupationRate' => $resource->getOccupationRate(),
            'availabilityRate' => $resource->getAvailabilityRate()
        ]);
    }

    #[Route('/api/resources/grouped-by-pole', name: 'api_resources_grouped_by_pole')]
    public function getResourcesGroupedByPole(Request $request, ResourceRepository $resourceRepository): JsonResponse
    {
        $date = null;
        if ($request->query->has('date')) {
            try {
                $date = new \DateTime($request->query->get('date'));
            } catch (\Exception $e) {
                $date = new \DateTime();
            }
        }
        
        $resources = $resourceRepository->findAllGroupedByPole($date);
        
        return new JsonResponse($resources);
    }
}