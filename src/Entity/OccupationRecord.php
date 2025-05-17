<?php

namespace App\Entity;

use App\Repository\OccupationRecordRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OccupationRecordRepository::class)]
class OccupationRecord
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Resource::class, inversedBy: 'occupationRecords')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Resource $resource = null;

    #[ORM\Column(type: 'date')]
    private ?\DateTimeInterface $weekStart = null;
    
    #[ORM\Column(type: 'date')]
    private ?\DateTimeInterface $weekEnd = null;

    #[ORM\Column(type: 'integer')]
    private int $occupationRate = 0;

    #[ORM\ManyToOne(targetEntity: Project::class)]
    private ?Project $project = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $updatedBy = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getResource(): ?Resource
    {
        return $this->resource;
    }

    public function setResource(?Resource $resource): self
    {
        $this->resource = $resource;
        return $this;
    }
    
    public function getDate(): ?\DateTimeInterface
    {
        return $this->weekStart;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->weekStart = $this->getWeekStartFromDate($date);
        $this->weekEnd = $this->getWeekEndFromDate($date);
        return $this;
    }
    
    public function getWeekStart(): ?\DateTimeInterface
    {
        return $this->weekStart;
    }
    
    public function setWeekStart(\DateTimeInterface $date): self
    {
        $this->weekStart = $this->getWeekStartFromDate($date);
        $this->weekEnd = $this->getWeekEndFromDate($date);
        return $this;
    }
    
    public function getWeekEnd(): ?\DateTimeInterface
    {
        return $this->weekEnd;
    }
    
    public function setWeekEnd(\DateTimeInterface $date): self
    {
        $this->weekEnd = $date;
        return $this;
    }
    
    public function getWeekStartFromDate(\DateTimeInterface $date): \DateTimeInterface
    {
        $weekStart = clone $date;
        $weekStart->modify('monday this week');
        return $weekStart;
    }
    
    public function getWeekEndFromDate(\DateTimeInterface $date): \DateTimeInterface
    {
        $weekEnd = clone $date;
        $weekEnd->modify('sunday this week');
        return $weekEnd;
    }
    
    public function isDateInWeek(\DateTimeInterface $date): bool
    {
        $dateTimestamp = $date->getTimestamp();
        $weekStartTimestamp = $this->weekStart->getTimestamp();
        $weekEndTimestamp = $this->weekEnd->getTimestamp();
        
        return ($dateTimestamp >= $weekStartTimestamp && $dateTimestamp <= $weekEndTimestamp);
    }

    public function getOccupationRate(): int
    {
        return $this->occupationRate;
    }

    public function setOccupationRate(int $occupationRate): self
    {
        $this->occupationRate = $occupationRate;
        return $this;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): self
    {
        $this->project = $project;
        return $this;
    }

    public function getUpdatedBy(): ?User
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(?User $updatedBy): self
    {
        $this->updatedBy = $updatedBy;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}