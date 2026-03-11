<?php

namespace App\Entity;

use App\Repository\MoodLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MoodLogRepository::class)]
#[ORM\HasLifecycleCallbacks]
class MoodLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(options: ['default' => 5])]
    private int $mood = 5;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $note = null;

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
    public function getNote(): ?string { return $this->note; }
    public function setNote(?string $n): static { $this->note = $n; return $this; }
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
