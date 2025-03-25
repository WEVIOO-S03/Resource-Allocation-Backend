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
            ];
        }

        return $this->json($formattedProjects);
    }
}