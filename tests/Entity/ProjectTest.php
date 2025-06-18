<?php

namespace App\Tests\Entity;

use App\Entity\Project;
use App\Entity\Resource;
use App\Entity\User;
use App\Entity\UserProjectAccess;
use PHPUnit\Framework\TestCase;
use Doctrine\Common\Collections\Collection;

class ProjectTest extends TestCase
{
    public function testProjectCanBeCreated()
    {
        $project = new Project();
        $project->setName('My Test Project');
        $project->setCode('TEST001');
        
        $this->assertEquals('My Test Project', $project->getName());
        $this->assertEquals('TEST001', $project->getCode());
    }
    
    public function testProjectCanHaveSkills()
    {
        $project = new Project();
        $skills = ['PHP', 'JavaScript', 'HTML'];
        $project->setRequiredSkills($skills);
        
        $this->assertEquals($skills, $project->getRequiredSkills());
        $this->assertCount(3, $project->getRequiredSkills());
    }
    
    public function testProjectInitializesCollections()
    {
        $project = new Project();
        
        $this->assertInstanceOf(Collection::class, $project->getUserAccess());
        $this->assertInstanceOf(Collection::class, $project->getResources());
        $this->assertCount(0, $project->getUserAccess());
        $this->assertCount(0, $project->getResources());
    }
    
    public function testProjectCanAddAndRemoveResources()
    {
        $project = new Project();
        $resource = $this->createMock(Resource::class);
        
        $resource->expects($this->once())
                ->method('addProject')
                ->with($this->equalTo($project));
                
        $resource->expects($this->once())
                ->method('removeProject')
                ->with($this->equalTo($project));
        
        $project->addResource($resource);
        
        $project->removeResource($resource);
    }
    
    public function testGetUsersReturnsUsersFromAccess()
    {
        $project = new Project();
        $user1 = $this->createMock(User::class);
        $user2 = $this->createMock(User::class);
        
        $access1 = $this->createMock(UserProjectAccess::class);
        $access1->expects($this->once())
                ->method('getUser')
                ->willReturn($user1);
                
        $access2 = $this->createMock(UserProjectAccess::class);
        $access2->expects($this->once())
                ->method('getUser')
                ->willReturn($user2);
        
        $reflection = new \ReflectionClass(Project::class);
        $property = $reflection->getProperty('userAccess');
        $property->setAccessible(true);
        $userAccess = $property->getValue($project);
        $userAccess->add($access1);
        $userAccess->add($access2);
        
        $users = $project->getUsers();
        $this->assertCount(2, $users);
        $this->assertTrue($users->contains($user1));
        $this->assertTrue($users->contains($user2));
    }
}