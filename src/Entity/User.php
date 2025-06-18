<?php

namespace App\Entity;

use App\Enum\UserStatus;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column]
    private ?bool $isActive = false;

    #[ORM\Column(type: 'string', enumType: UserStatus::class)]
    private UserStatus $status = UserStatus::PENDING;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(length: 255)]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    private ?string $lastName = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $position = null;
    
    #[ORM\Column(type: 'json', nullable: true)]
    private array $skills = [];

    

    #[ORM\OneToMany(targetEntity: UserProjectAccess::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private Collection $projectAccess;

    #[ORM\OneToMany(targetEntity: Resource::class, mappedBy: 'projectManager')]
    private Collection $managedResources;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->projectAccess = new ArrayCollection();
        $this->managedResources = new ArrayCollection(); 
    }

    // Add these methods
    public function getManagedResources(): Collection
    {
        return $this->managedResources;
    }

    public function addManagedResource(Resource $resource): self
    {
        if (!$this->managedResources->contains($resource)) {
            $this->managedResources->add($resource);
            $resource->setProjectManager($this);
        }
        return $this;
    }

    public function removeManagedResource(Resource $resource): self
    {
        if ($this->managedResources->removeElement($resource)) {
            // Set the project manager to null if it's set to this user
            if ($resource->getProjectManager() === $this) {
                $resource->setProjectManager(null);
            }
        }
        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
{
    $roles = $this->roles ?? []; 
    $roles[] = 'ROLE_USER';

    foreach ($this->projectAccess as $access) {
        if ($access->getCanEdit() && !in_array('ROLE_EDITOR', $roles)) {
            $roles[] = 'ROLE_EDITOR';
        }
        if ($access->getCanConsult() && !in_array('ROLE_CONSULTANT', $roles)) {
            $roles[] = 'ROLE_CONSULTANT';
        }
    }

    return array_unique($roles);
}



    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function eraseCredentials(): void
    {
        // Clear sensitive data if necessary
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getStatus(): UserStatus
    {
        return $this->status;
    }

    public function setStatus(UserStatus $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;
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

    public function getProjectAccess(): Collection
    {
        return $this->projectAccess;
    }

    public function addProjectAccess(Project $project, bool $canConsult = false, bool $canEdit = false): self
    {
        foreach ($this->projectAccess as $access) {
            if ($access->getProject()->getId() === $project->getId()) {
                $access->setCanConsult($canConsult);
                $access->setCanEdit($canEdit);
                return $this;
            }
        }

        $access = new UserProjectAccess();
        $access->setUser($this);
        $access->setProject($project);
        $access->setCanConsult($canConsult);
        $access->setCanEdit($canEdit);
        
        $this->projectAccess->add($access);
        return $this;
    }

    public function removeProjectAccess(Project $project): self
{
    foreach ($this->projectAccess as $access) {
        if ($access->getProject()->getId() === $project->getId()) {
            $this->projectAccess->removeElement($access);
            break;
        }
    }
    return $this;
}

    public function getAccessibleProjects(): Collection
    {
        $projects = new ArrayCollection();
        foreach ($this->projectAccess as $access) {
            $projects->add($access->getProject());
        }
        return $projects;
    }

    public function canEditProject(Project $project): bool
    {
        foreach ($this->projectAccess as $access) {
            if ($access->getProject()->getId() === $project->getId()) {
                return $access->getCanEdit();
            }
        }
        return false;
    }
    
    public function canConsultProject(Project $project): bool
    {
        foreach ($this->projectAccess as $access) {
            if ($access->getProject()->getId() === $project->getId()) {
                return $access->getCanConsult();
            }
        }
        return false;
    }
}


