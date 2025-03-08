<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Project;
use App\Enum\UserStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/users', name: 'admin_list_users', methods: ['GET'])]
    public function listUsers(EntityManagerInterface $entityManager, SerializerInterface $serializer): JsonResponse
    {
        $users = $entityManager->getRepository(User::class)->findAll();
        
        $userData = [];
        foreach ($users as $user) {
            $userData[] = [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'status' => $user->getStatus()->value,
                'isActive' => $user->isActive(),
                'projects' => array_map(fn($access) => [
                'projectId' => $access->getProject()->getId(),
                'canEdit' => $access->getCanEdit(),
                'canConsult' => $access->getCanConsult()
            ], $user->getProjectAccess()->toArray()),

                'createdAt' => $user->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }
        
        return $this->json($userData);
    }
    
    #[Route('/users/{id}', name: 'admin_get_user', methods: ['GET'])]
    public function getUserById(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $entityManager->getRepository(User::class)->find($id);
        
        if (!$user) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }
        
        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'status' => $user->getStatus()->value,
            'isActive' => $user->isActive(),
            'projects' => array_map(fn($access) => [
            'projectId' => $access->getProject()->getId(),
            'canEdit' => $access->getCanEdit(),
            'canConsult' => $access->getCanConsult()
        ], $user->getProjectAccess()->toArray()),

            'createdAt' => $user->getCreatedAt()->format('Y-m-d H:i:s'),
        ]);
    }
    
    #[Route('/users/{id}/approve', name: 'admin_approve_user', methods: ['PATCH'])]
    public function approveUser(
        int $id, 
        Request $request, 
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $user = $entityManager->getRepository(User::class)->find($id);
        
        if (!$user) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }
        
        $data = json_decode($request->getContent(), true);
        
        if (isset($data['position'])) {
            $user->setPosition($data['position']);
        }
        
        if (isset($data['skills']) && is_array($data['skills'])) {
            $user->setSkills($data['skills']);
        }
        
        if (isset($data['projects']) && is_array($data['projects'])) {
            $currentProjects = [];
            foreach ($user->getProjectAccess() as $access) {
                $currentProjects[$access->getProject()->getId()] = $access->getProject();
            }
            
            $assignedProjectIds = [];
            foreach ($data['projects'] as $projectAccess) {
                $projectId = $projectAccess['id'];
                $assignedProjectIds[] = $projectId;
                
                $canConsult = $projectAccess['canConsult'] ?? false;
                $canEdit = $projectAccess['canEdit'] ?? false;
                
                $project = $entityManager->getRepository(Project::class)->find($projectId);
                if ($project) {
                    $user->addProjectAccess($project, $canConsult, $canEdit);
                }
            }
            
            foreach ($currentProjects as $projectId => $project) {
                if (!in_array($projectId, $assignedProjectIds)) {
                    $user->removeProjectAccess($project);
                }
            }
        }
        
        $user->setStatus(UserStatus::APPROVED);
        $user->setIsActive(true);
        
        $entityManager->flush();
        
        return $this->json([
            'message' => 'User approved successfully',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'position' => $user->getPosition(),
                'skills' => $user->getSkills(),
                'status' => $user->getStatus()->value,
                'projects' => array_map(function($access) {
                    return [
                        'id' => $access->getProject()->getId(),
                        'code' => $access->getProject()->getCode(),
                        'name' => $access->getProject()->getName(),
                        'canConsult' => $access->getCanConsult(),
                        'canEdit' => $access->getCanEdit()
                    ];
                }, $user->getProjectAccess()->toArray())
            ]
        ]);
    }
    
    #[Route('/users/{id}/update-rights', name: 'admin_update_user_rights', methods: ['PATCH'])]
public function updateUserRights(int $id, Request $request, EntityManagerInterface $entityManager): JsonResponse
{
    $user = $entityManager->getRepository(User::class)->find($id);
    
    if (!$user) {
        return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
    }
    
    $data = json_decode($request->getContent(), true);
    
    if (isset($data['projectId'])) {
        $project = $entityManager->getRepository(Project::class)->find($data['projectId']);
        
        if (!$project) {
            return $this->json(['error' => 'Project not found'], Response::HTTP_NOT_FOUND);
        }
        
        $canConsult = $data['canConsult'] ?? false;
        $canEdit = $data['canEdit'] ?? false;
        
        $user->addProjectAccess($project, $canConsult, $canEdit);
    }
    
    $entityManager->flush();
    
    return $this->json([
        'message' => 'User rights updated successfully'
    ]);
}
}