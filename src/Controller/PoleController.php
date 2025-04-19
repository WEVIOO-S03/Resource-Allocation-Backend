<?php

namespace App\Controller;

use App\Entity\Pole;
use App\Form\PoleType;
use App\Repository\PoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[Route('/api/poles')]
class PoleController extends AbstractController
{

    #[Route('/create', name: 'api_pole_create', methods: ['POST'])]
    public function apiCreate(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$this->isGranted('ROLE_USER') && !$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'You do not have permission to create poles'], 403);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['name'])) {
            return $this->json(['error' => 'Name is required'], 400);
        }

        $pole = new Pole();
        $pole->setName($data['name']);
        $pole->setCreatedBy($this->getUser());

        $entityManager->persist($pole);
        $entityManager->flush();

        return $this->json([
            'id' => $pole->getId(),
            'name' => $pole->getName(),
            'createdBy' => $pole->getCreatedBy()->getFirstName() . ' ' . $pole->getCreatedBy()->getLastName(),
            'resourceCount' => count($pole->getResources())
        ], 201);
    }

    
}