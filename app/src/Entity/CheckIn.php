<?php

namespace App\Entity;

use App\Repository\CheckInRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CheckInRepository::class)]
#[ORM\HasLifecycleCallbacks]
class CheckIn
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'checkIns')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    // 1-10 scale
    #[ORM\Column(options: ['default' => 5])]
    private int $mood = 5;

    // 0-10 craving intensity
    #[ORM\Column(options: ['default' => 0])]
    private int $cravingIntensity = 0;

    // HALT: hungry, angry, lonely, tired
    #[ORM\Column(type: Types::JSON)]
    private array $triggers = [];

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }
    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $u): static { $this->user = $u; return $this; }
    public function getMood(): int { return $this->mood; }
    public function setMood(int $m): static { $this->mood = $m; return $this; }
    public function getCravingIntensity(): int { return $this->cravingIntensity; }
    public function setCravingIntensity(int $c): static { $this->cravingIntensity = $c; return $this; }
    public function getTriggers(): array { return $this->triggers; }
    public function setTriggers(array $t): static { $this->triggers = $t; return $this; }
    public function getNotes(): ?string { return $this->notes; }
    public function setNotes(?string $n): static { $this->notes = $n; return $this; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }

    public function getMoodEmoji(): string
    {
        return match(true) {
            $this->mood >= 9 => '😄',
            $this->mood >= 7 => '😊',
            $this->mood >= 5 => '😐',
            $this->mood >= 3 => '😔',
            default          => '😢',
        };
    }
}
