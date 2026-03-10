<?php

namespace App\Entity;

use App\Repository\AchievementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AchievementRepository::class)]
class Achievement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $slug = '';

    #[ORM\Column(length: 100)]
    private string $name = '';

    #[ORM\Column(length: 255)]
    private string $description = '';

    #[ORM\Column(length: 10)]
    private string $icon = '🏆';

    #[ORM\Column(length: 50)]
    private string $category = 'streak'; // streak, craving, journal, health, money

    #[ORM\Column(options: ['default' => 0])]
    private int $requirementDays = 0;

    #[ORM\Column(options: ['default' => 0])]
    private int $requirementCount = 0;

    #[ORM\Column(options: ['default' => 100])]
    private int $xpReward = 100;

    #[ORM\OneToMany(targetEntity: UserAchievement::class, mappedBy: 'achievement')]
    private Collection $userAchievements;

    public function __construct()
    {
        $this->userAchievements = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getSlug(): string { return $this->slug; }
    public function setSlug(string $s): static { $this->slug = $s; return $this; }
    public function getName(): string { return $this->name; }
    public function setName(string $n): static { $this->name = $n; return $this; }
    public function getDescription(): string { return $this->description; }
    public function setDescription(string $d): static { $this->description = $d; return $this; }
    public function getIcon(): string { return $this->icon; }
    public function setIcon(string $i): static { $this->icon = $i; return $this; }
    public function getCategory(): string { return $this->category; }
    public function setCategory(string $c): static { $this->category = $c; return $this; }
    public function getRequirementDays(): int { return $this->requirementDays; }
    public function setRequirementDays(int $d): static { $this->requirementDays = $d; return $this; }
    public function getRequirementCount(): int { return $this->requirementCount; }
    public function setRequirementCount(int $c): static { $this->requirementCount = $c; return $this; }
    public function getXpReward(): int { return $this->xpReward; }
    public function setXpReward(int $x): static { $this->xpReward = $x; return $this; }
    public function getUserAchievements(): Collection { return $this->userAchievements; }
}
