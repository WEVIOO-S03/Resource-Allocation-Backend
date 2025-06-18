<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity]
#[ORM\Table(name: 'projects')]
class Project
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $code;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'json', nullable: true)]
    private array $requiredSkills = [];

    #[ORM\OneToMany(targetEntity: UserProjectAccess::class, mappedBy: 'project', cascade: ['persist', 'remove'])]
    private Collection $userAccess;

    #[ORM\ManyToMany(targetEntity: Resource::class, mappedBy: 'projects')]
    private Collection $resources;

    public function __construct()
    {
        $this->userAccess = new ArrayCollection();
        $this->resources = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getRequiredSkills(): array
    {
        return $this->requiredSkills;
    }
    
    public function setRequiredSkills(array $requiredSkills): self
    {
        $this->requiredSkills = $requiredSkills;
        return $this;
    }

    public function getUserAccess(): Collection
    {
        return $this->userAccess;
    }

    public function getUsers(): Collection
    {
        $users = new ArrayCollection();
        foreach ($this->userAccess as $access) {
            $users->add($access->getUser());
        }
        return $users;
    }

    public function getResources(): Collection
    {
        return $this->resources;
    }
    
    public function addResource(Resource $resource): self
    {
        if (!$this->resources->contains($resource)) {
            $this->resources->add($resource);
            $resource->addProject($this);
        }
        return $this;
    }
    
    public function removeResource(Resource $resource): self
    {
        if ($this->resources->contains($resource)) {
            $this->resources->removeElement($resource);
            $resource->removeProject($this);
        }
        return $this;
    }
}
