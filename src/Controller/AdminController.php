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
                'position' => $user->getPosition(),
                'skills' => $user->getSkills()
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
            'position' => $user->getPosition(),
            'skills' => $user->getSkills(),
        ]);
    }
    
    #[Route('/users/{id}/update-access', name: 'admin_update_user_access', methods: ['PATCH'])]
        public function updateUserAccess(
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
                $hasAccess = false;
                
                $existingAccess = [];
                foreach ($user->getProjectAccess() as $access) {
                    $existingAccess[$access->getProject()->getId()] = $access;
                }
                
                $projectsInRequest = [];
                
                foreach ($data['projects'] as $projectData) {
                    $projectId = $projectData['id'] ?? $projectData['projectId'] ?? null;
                    if (!$projectId) continue;
                    
                    $projectsInRequest[] = $projectId;
                    
                    $canConsult = $projectData['canConsult'] ?? false;
                    $canEdit = $projectData['canEdit'] ?? false;
                    
                    if ($canConsult || $canEdit) {
                        $hasAccess = true;
                        $project = $entityManager->getRepository(Project::class)->find($projectId);
                        if ($project) {
                            $user->addProjectAccess($project, $canConsult, $canEdit);
                        }
                    } else if (isset($existingAccess[$projectId])) {
                        $access = $existingAccess[$projectId];
                        $user->getProjectAccess()->removeElement($access);
                        $entityManager->remove($access);
                    }
                }
                
                foreach ($existingAccess as $projectId => $access) {
                    if (!in_array($projectId, $projectsInRequest)) {
                        $user->getProjectAccess()->removeElement($access);
                        $entityManager->remove($access);
                    }
                }
                
                if ($hasAccess) {
                    $user->setStatus(UserStatus::APPROVED);
                    $user->setIsActive(true);
                } else {
                    $user->setStatus(UserStatus::PENDING);
                    $user->setIsActive(false);
                }
            }
            
            try {
                $entityManager->persist($user);
                $entityManager->flush();
                $entityManager->clear(UserProjectAccess::class);
                
                $refreshedUser = $entityManager->getRepository(User::class)->find($id);
                
                return $this->json([
                    'message' => 'User updated successfully',
                    'user' => [
                        'id' => $refreshedUser->getId(),
                        'email' => $refreshedUser->getEmail(),
                        'position' => $refreshedUser->getPosition(),
                        'skills' => $refreshedUser->getSkills(),
                        'status' => $refreshedUser->getStatus()->value,
                        'projects' => array_map(function($access) {
                            return [
                                'id' => $access->getProject()->getId(),
                                'code' => $access->getProject()->getCode(),
                                'name' => $access->getProject()->getName(),
                                'canConsult' => $access->getCanConsult(),
                                'canEdit' => $access->getCanEdit()
                            ];
                        }, $refreshedUser->getProjectAccess()->toArray())
                    ]
                ]);
            } catch (\Exception $e) {
                return $this->json([
                    'error' => 'Failed to update user: ' . $e->getMessage()
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
}



    #[Route('/users/{id}', name: 'admin_delete_user', methods: ['DELETE'])]
    public function deleteUser(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $entityManager->getRepository(User::class)->find($id);
        
        if (!$user) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }
        
        $currentUser = $this->getUser();
        if ($currentUser && $currentUser->getId() === $user->getId()) {
            return $this->json(['error' => 'You cannot delete your own account'], Response::HTTP_FORBIDDEN);
        }
        
        foreach ($user->getProjectAccess() as $access) {
            $user->removeProjectAccess($access->getProject());
        }
        
        $entityManager->remove($user);
        $entityManager->flush();
        
        return $this->json(['message' => 'User deleted successfully']);
    }
}