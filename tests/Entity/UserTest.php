<?php

namespace App\Tests\Entity;

use App\Entity\User;
use App\Entity\Project;
use App\Entity\Resource;
use App\Entity\UserProjectAccess;
use App\Enum\UserStatus;
use PHPUnit\Framework\TestCase;
use Doctrine\Common\Collections\Collection;

class UserTest extends TestCase
{
    public function testUserCanBeCreated()
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setFirstName('Test');
        $user->setLastName('Testt');
        $user->setPosition('Developer');
        
        $this->assertEquals('test@example.com', $user->getEmail());
        $this->assertEquals('Test', $user->getFirstName());
        $this->assertEquals('Testt', $user->getLastName());
        $this->assertEquals('Developer', $user->getPosition());
    }
    
    public function testUserInitializesWithDefaults()
    {
        $user = new User();
        
        $this->assertFalse($user->isActive());
        $this->assertEquals(UserStatus::PENDING, $user->getStatus());
        $this->assertInstanceOf(\DateTimeImmutable::class, $user->getCreatedAt());
        
        $this->assertInstanceOf(Collection::class, $user->getProjectAccess());
        $this->assertInstanceOf(Collection::class, $user->getManagedResources());
        $this->assertCount(0, $user->getProjectAccess());
        $this->assertCount(0, $user->getManagedResources());
    }
    
    public function testUserIdentifierIsEmail()
    {
        $user = new User();
        $user->setEmail('test@example.com');
        
        $this->assertEquals('test@example.com', $user->getUserIdentifier());
    }
    
    public function testUserCanHaveSkills()
    {
        $user = new User();
        $skills = ['PHP', 'JavaScript', 'React'];
        $user->setSkills($skills);
        
        $this->assertEquals($skills, $user->getSkills());
        $this->assertCount(3, $user->getSkills());
    }
    
    public function testUserCanAddAndRemoveSkills()
    {
        $user = new User();
        
        $user->addSkill('PHP');
        $user->addSkill('JavaScript');
        $this->assertCount(2, $user->getSkills());
        $this->assertContains('PHP', $user->getSkills());
        $this->assertContains('JavaScript', $user->getSkills());
        
        $user->addSkill('PHP');
        $this->assertCount(2, $user->getSkills());
        
        $user->removeSkill('PHP');
        $this->assertCount(1, $user->getSkills());
        $this->assertNotContains('PHP', $user->getSkills());
        $this->assertContains('JavaScript', $user->getSkills());
    }
    
    public function testUserHasDefaultRole()
    {
        $user = new User();
        $roles = $user->getRoles();
        
        $this->assertContains('ROLE_USER', $roles);
        $this->assertCount(1, $roles);
    }
    
    public function testUserCanSetCustomRoles()
    {
        $user = new User();
        $customRoles = ['ROLE_ADMIN', 'ROLE_MANAGER'];
        $user->setRoles($customRoles);
        
        $roles = $user->getRoles();
        $this->assertContains('ROLE_USER', $roles); // always has ROLE_USER
        $this->assertContains('ROLE_ADMIN', $roles);
        $this->assertContains('ROLE_MANAGER', $roles);
    }
    
    public function testUserCanManageResources()
{
    $user = new User();
    
    $resource = new Resource();
    
    $user->addManagedResource($resource);
    $this->assertCount(1, $user->getManagedResources());
    $this->assertSame($user, $resource->getProjectManager());
    
    $user->removeManagedResource($resource);
    $this->assertCount(0, $user->getManagedResources());
    $this->assertNull($resource->getProjectManager());
}
    
    public function testUserCanAddProjectAccess()
    {
        $user = new User();
        $project = $this->createMock(Project::class);
        $project->method('getId')->willReturn(1);
        
        $user->addProjectAccess($project, true, false); 
        
        $this->assertCount(1, $user->getProjectAccess());
        $this->assertTrue($user->canConsultProject($project));
        $this->assertFalse($user->canEditProject($project));
    }
    
    public function testUserCanUpdateExistingProjectAccess()
    {
        $user = new User();
        $project = $this->createMock(Project::class);
        $project->method('getId')->willReturn(1);
        
        $user->addProjectAccess($project, true, false);
        $this->assertTrue($user->canConsultProject($project));
        $this->assertFalse($user->canEditProject($project));
        
        $user->addProjectAccess($project, true, true);
        $this->assertTrue($user->canConsultProject($project));
        $this->assertTrue($user->canEditProject($project));
        
        $this->assertCount(1, $user->getProjectAccess());
    }
    
    public function testUserCanRemoveProjectAccess()
    {
        $user = new User();
        $project = $this->createMock(Project::class);
        $project->method('getId')->willReturn(1);
        
        $user->addProjectAccess($project, true, true);
        $this->assertCount(1, $user->getProjectAccess());
        
        $user->removeProjectAccess($project);
        $this->assertCount(0, $user->getProjectAccess());
    }
    
    public function testUserStatusCanBeChanged()
    {
        $user = new User();
        
        $this->assertEquals(UserStatus::PENDING, $user->getStatus());
        
        $user->setStatus(UserStatus::APPROVED);
        $this->assertEquals(UserStatus::APPROVED, $user->getStatus());
    }
    
    public function testUserCanBeActivatedAndDeactivated()
    {
        $user = new User();
        
        $this->assertFalse($user->isActive());
        
        $user->setIsActive(true);
        $this->assertTrue($user->isActive());
        
        $user->setIsActive(false);
        $this->assertFalse($user->isActive());
    }
    
    public function testPasswordCanBeSetAndRetrieved()
    {
        $user = new User();
        $password = 'hashed_password_123';
        
        $user->setPassword($password);
        $this->assertEquals($password, $user->getPassword());
    }
    
    public function testCreatedAtIsSetOnConstruction()
    {
        $beforeCreation = new \DateTimeImmutable();
        $user = new User();
        $afterCreation = new \DateTimeImmutable();
        
        $createdAt = $user->getCreatedAt();
        $this->assertInstanceOf(\DateTimeImmutable::class, $createdAt);
        $this->assertGreaterThanOrEqual($beforeCreation, $createdAt);
        $this->assertLessThanOrEqual($afterCreation, $createdAt);
    }
}