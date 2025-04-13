<?php

namespace App\Controller;

use App\Repository\ProjectRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/user')]
#[IsGranted('ROLE_USER')]
class UserController extends AbstractController
{
    #[Route('/projects', name: 'api_user_projects', methods: ['GET'])]
    public function getUserProjects(ProjectRepository $projectRepository): JsonResponse
    {
        $user = $this->getUser();
        $projects = $user->getAccessibleProjects();

        $formattedProjects = [];
        foreach ($projects as $project) {
            $formattedProjects[] = [
                'id' => $project->getId(),
                'code' => $project->getCode(),
                'name' => $project->getName(),
                'requiredSkills' => $project->getRequiredSkills(),
                'canEdit' => $user->canEditProject($project),
                'canConsult' => $user->canConsultProject($project)
            ];
        }

        return $this->json($formattedProjects);
    }
} 