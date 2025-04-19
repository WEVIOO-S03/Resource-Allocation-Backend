<?php

namespace App\Entity;

use App\Repository\ResourceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ResourceRepository::class)]
class Resource
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $fullName = null;

    #[ORM\ManyToOne(inversedBy: 'resources')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Pole $pole = null;

    #[ORM\Column(type: 'json')]
    private array $skills = [];

    #[ORM\Column(type: 'float')]
    private ?float $occupationRate = 0.0; 

    #[ORM\Column(type: 'float')]
    private ?float $availabilityRate = 100.0; 

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $position = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $projectManager = null;

    #[ORM\ManyToMany(targetEntity: Project::class)]
    #[ORM\JoinTable(name: 'resource_projects')]
    private Collection $projects;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatar = null;
    
    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;
    
    #[ORM\OneToMany(targetEntity: OccupationRecord::class, mappedBy: 'resource', cascade: ['persist', 'remove'])]
    private Collection $occupationRecords;

    public function __construct()
    {
        $this->projects = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->occupationRecords = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function setFullName(string $fullName): self
    {
        $this->fullName = $fullName;
        return $this;
    }

    public function getPole(): ?Pole
    {
        return $this->pole;
    }

    public function setPole(?Pole $pole): self
    {
        $this->pole = $pole;
        return $this;
    }

    public function getSkills(): array
    {
        return $this->skills;
    }

    public function setSkills(array $skills): self
    {
        $this->skills = $skills;
        return $this;
    }

    public function addSkill(string $skill): self
    {
        if (!in_array($skill, $this->skills)) {
            $this->skills[] = $skill;
        }
        return $this;
    }

    public function removeSkill(string $skill): self
    {
        $key = array_search($skill, $this->skills);
        if ($key !== false) {
            unset($this->skills[$key]);
            $this->skills = array_values($this->skills);
        }
        return $this;
    }

    public function getOccupationRate(): ?float
    {
        return $this->occupationRate;
    }

    public function setOccupationRate(float $occupationRate): self
    {
        $this->occupationRate = $occupationRate;
        $this->setAvailabilityRate(100 - $occupationRate);
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getAvailabilityRate(): ?float
    {
        return $this->availabilityRate;
    }

    public function setAvailabilityRate(float $availabilityRate): self
    {
        $this->availabilityRate = $availabilityRate;
        return $this;
    }

    public function getPosition(): ?string
    {
        return $this->position;
    }

    public function setPosition(?string $position): self
    {
        $this->position = $position;
        return $this;
    }

    public function getProjectManager(): ?User
    {
        return $this->projectManager;
    }

    public function setProjectManager(?User $projectManager): self
    {
        $this->projectManager = $projectManager;
        return $this;
    }

    public function getProjects(): Collection
    {
        return $this->projects;
    }

    public function addProject(Project $project): self
    {
        if (!$this->projects->contains($project)) {
            $this->projects->add($project);
        }
        return $this;
    }

    public function removeProject(Project $project): self
    {
        $this->projects->removeElement($project);
        return $this;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): self
    {
        $this->avatar = $avatar;
        return $this;
    }
    
    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }
    
    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
    public function getOccupationRecords(): Collection
{
    return $this->occupationRecords;
}

public function addOccupationRecord(OccupationRecord $record): self
{
    if (!$this->occupationRecords->contains($record)) {
        $this->occupationRecords->add($record);
        $record->setResource($this);
    }
    return $this;
}

public function removeOccupationRecord(OccupationRecord $record): self
{
    if ($this->occupationRecords->removeElement($record)) {
        if ($record->getResource() === $this) {
            $record->setResource(null);
        }
    }
    return $this;
}

public function getOccupationRateForDate(\DateTimeInterface $date): int
{
    $dateString = $date->format('Y-m-d');
    
    foreach ($this->occupationRecords as $record) {
        if ($record->getDate()->format('Y-m-d') === $dateString) {
            return $record->getOccupationRate();
        }
    }
    
    return $this->occupationRate; 
}
}