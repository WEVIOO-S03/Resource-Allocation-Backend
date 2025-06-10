<?php

namespace App\Tests\Entity;

use App\Entity\Resource;
use App\Entity\User;
use App\Entity\Project;
use App\Entity\Pole;
use App\Entity\OccupationRecord;
use PHPUnit\Framework\TestCase;
use Doctrine\Common\Collections\Collection;

class ResourceTest extends TestCase
{
    public function testResourceCanBeCreated()
    {
        $resource = new Resource();
        $resource->setFullName('Manou Manou');
        $resource->setPosition('Senior Developer');
        
        $this->assertEquals('Manou Manou', $resource->getFullName());
        $this->assertEquals('Senior Developer', $resource->getPosition());
    }
    
    public function testResourceInitializesWithDefaults()
    {
        $resource = new Resource();
        
        $this->assertInstanceOf(\DateTimeImmutable::class, $resource->getCreatedAt());
        $this->assertNull($resource->getUpdatedAt());
        
        $this->assertInstanceOf(Collection::class, $resource->getProjects());
        $this->assertInstanceOf(Collection::class, $resource->getOccupationRecords());
        $this->assertCount(0, $resource->getProjects());
        $this->assertCount(0, $resource->getOccupationRecords());
        
        $this->assertEquals([], $resource->getSkills());
        $this->assertEquals([], $resource->getProjectOccupations());
    }
    
    public function testResourceCanHaveSkills()
    {
        $resource = new Resource();
        $skills = ['PHP', 'JavaScript', 'React'];
        $resource->setSkills($skills);
        
        $this->assertEquals($skills, $resource->getSkills());
        $this->assertCount(3, $resource->getSkills());
    }
    
    public function testResourceCanAddAndRemoveSkills()
    {
        $resource = new Resource();
        
        $resource->addSkill('PHP');
        $resource->addSkill('JavaScript');
        $this->assertCount(2, $resource->getSkills());
        $this->assertContains('PHP', $resource->getSkills());
        $this->assertContains('JavaScript', $resource->getSkills());
        
        $resource->addSkill('PHP');
        $this->assertCount(2, $resource->getSkills());
        
        $resource->removeSkill('PHP');
        $this->assertCount(1, $resource->getSkills());
        $this->assertNotContains('PHP', $resource->getSkills());
        $this->assertContains('JavaScript', $resource->getSkills());
    }
    
    public function testResourceCanHaveProjectManager()
    {
        $resource = new Resource();
        $manager = new User();
        $manager->setFirstName('Jane');
        $manager->setLastName('Manager');
        
        $resource->setProjectManager($manager);
        
        $this->assertSame($manager, $resource->getProjectManager());
    }
    
    public function testResourceCanHavePole()
    {
        $resource = new Resource();
        $pole = $this->createMock(Pole::class);
        
        $resource->setPole($pole);
        
        $this->assertSame($pole, $resource->getPole());
    }
    
    public function testResourceCanAddAndRemoveProjects()
    {
        $resource = new Resource();
        $project1 = new Project();
        $project2 = new Project();
        
        $resource->addProject($project1);
        $resource->addProject($project2);
        $this->assertCount(2, $resource->getProjects());
        $this->assertTrue($resource->getProjects()->contains($project1));
        $this->assertTrue($resource->getProjects()->contains($project2));
        
        $resource->addProject($project1);
        $this->assertCount(2, $resource->getProjects());
        
        $resource->removeProject($project1);
        $this->assertCount(1, $resource->getProjects());
        $this->assertFalse($resource->getProjects()->contains($project1));
        $this->assertTrue($resource->getProjects()->contains($project2));
    }
    
    public function testResourceCanManageOccupationRecords()
    {
        $resource = new Resource();
        $record = new OccupationRecord();
        
        // Test adding occupation record
        $resource->addOccupationRecord($record);
        $this->assertCount(1, $resource->getOccupationRecords());
        $this->assertTrue($resource->getOccupationRecords()->contains($record));
        $this->assertSame($resource, $record->getResource());
        
        // Test removing occupation record
        $resource->removeOccupationRecord($record);
        $this->assertCount(0, $resource->getOccupationRecords());
        $this->assertFalse($resource->getOccupationRecords()->contains($record));
    }
    
    public function testResourceAvatarHandling()
    {
        $resource = new Resource();
        
        $defaultAvatar = $resource->getAvatar();
        $this->assertStringContainsString('pravatar.cc', $defaultAvatar);
        
        $customAvatar = 'https://example.com/avatar.jpg';
        $resource->setAvatar($customAvatar);
        $this->assertEquals($customAvatar, $resource->getAvatar());
    }
    
    public function testResourceTimestamps()
    {
        $beforeCreation = new \DateTimeImmutable();
        $resource = new Resource();
        $afterCreation = new \DateTimeImmutable();
        
        $createdAt = $resource->getCreatedAt();
        $this->assertInstanceOf(\DateTimeImmutable::class, $createdAt);
        $this->assertGreaterThanOrEqual($beforeCreation, $createdAt);
        $this->assertLessThanOrEqual($afterCreation, $createdAt);
        
        $this->assertNull($resource->getUpdatedAt());
        
        $updatedTime = new \DateTimeImmutable();
        $resource->setUpdatedAt($updatedTime);
        $this->assertSame($updatedTime, $resource->getUpdatedAt());
    }
    
    public function testResourceOccupationRateForCurrentWeek()
    {
        $resource = new Resource();
        $today = new \DateTime();
        
        $occupationRate = $resource->getOccupationRateForWeek($today);
        $this->assertEquals(0, $occupationRate);
        
        $resource->setDirectOccupationRate(75.5);
        $this->assertEquals(75.5, $resource->getOccupationRate());
    }
    
    public function testResourceWeeklyOccupationWithNoRecords()
    {
        $resource = new Resource();
        $today = new \DateTime();
        
        $weeklyOccupation = $resource->getWeeklyOccupation($today);
        
        $this->assertIsArray($weeklyOccupation);
        $this->assertArrayHasKey('total', $weeklyOccupation);
        $this->assertArrayHasKey('byProject', $weeklyOccupation);
        $this->assertEquals(0, $weeklyOccupation['total']);
        $this->assertEquals([], $weeklyOccupation['byProject']);
    }
    
    public function testResourceProjectOccupations()
    {
        $resource = new Resource();
        $occupations = [
            'project1' => 50,
            'project2' => 30
        ];
        
        $resource->setProjectOccupations($occupations);
        
        $this->assertEquals($occupations, $resource->getProjectOccupations());
    }
    
    public function testResourceCreatedAtIsImmutable()
    {
        $resource = new Resource();
        $originalCreatedAt = $resource->getCreatedAt();
        
        $newTime = new \DateTimeImmutable('2023-01-01');
        $resource->setCreatedAt($newTime);
        
        $this->assertEquals($newTime, $resource->getCreatedAt());
        $this->assertNotSame($originalCreatedAt, $resource->getCreatedAt());
    }
}
