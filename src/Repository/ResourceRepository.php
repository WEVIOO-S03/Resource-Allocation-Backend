<?php

namespace App\Repository;

use App\Entity\Resource;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ResourceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Resource::class);
    }

    public function findByProject(int $projectId)
    {
        return $this->createQueryBuilder('r')
            ->select('r, p')
            ->leftJoin('r.pole', 'p')
            ->leftJoin('r.projects', 'pr')
            ->where('pr.id = :projectId')
            ->setParameter('projectId', $projectId)
            ->getQuery()
            ->getResult();
    }

    public function findAllGroupedByPole(\DateTimeInterface $date = null)
    {
        $date = $date ?: new \DateTime();
        
        $resources = $this->createQueryBuilder('r')
            ->select('r, p, o')
            ->leftJoin('r.pole', 'p')
            ->leftJoin('r.occupationRecords', 'o', 'WITH', 'o.date = :date')
            ->setParameter('date', $date->format('Y-m-d'))
            ->orderBy('p.name', 'ASC')
            ->addOrderBy('r.fullName', 'ASC')
            ->getQuery()
            ->getResult();
        
        $grouped = [];
        foreach ($resources as $resource) {
            $poleName = $resource->getPole() ? $resource->getPole()->getName() : 'Unassigned';
            if (!isset($grouped[$poleName])) {
                $grouped[$poleName] = [];
            }
            
            
            $occupationRate = 0;
            foreach ($resource->getOccupationRecords() as $record) {
                if ($record->getDate()->format('Y-m-d') === $date->format('Y-m-d')) {
                    $occupationRate = $record->getOccupationRate();
                    break;
                }
            }
            
            $grouped[$poleName][] = $resource;
        }
        
        $result = [];
        foreach ($grouped as $poleName => $poleResources) {
            $result[] = [
                'name' => $poleName,
                'employees' => array_map(function($resource) use ($date) {
                    return [
                        'id' => $resource->getId(),
                        'name' => $resource->getFullName(),
                        'title' => $resource->getPosition(),
                        'avatar' => $resource->getAvatar() ?: 'https://i.pravatar.cc/150?img=' . $resource->getId(),
                        'occupationRate' => $resource->getOccupationRateForDate($date),
                        'availabilityRate' => $resource->getAvailabilityRate(),
                        'skills' => $resource->getSkills(),
                        'projectManager' => $resource->getProjectManager() ? $resource->getProjectManager()->getFirstName() . ' ' . $resource->getProjectManager()->getLastName() : null,
                        'projects' => $resource->getProjects()->map(fn($p) => $p->getName())->toArray()
                    ];
                }, $poleResources)
            ];
        }
        
        return $result;
    }
}