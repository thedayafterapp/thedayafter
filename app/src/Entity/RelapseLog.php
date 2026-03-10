<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class RelapseLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'relapseLogs')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 20)]
    private string $addictionType = 'alcohol';

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $relapsedAt = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $previousQuitDate = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $moneySavedAtRelapse = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->relapsedAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }
    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $u): static { $this->user = $u; return $this; }
    public function getAddictionType(): string { return $this->addictionType; }
    public function setAddictionType(string $t): static { $this->addictionType = $t; return $this; }
    public function getRelapsedAt(): ?\DateTimeInterface { return $this->relapsedAt; }
    public function getPreviousQuitDate(): ?\DateTimeInterface { return $this->previousQuitDate; }
    public function setPreviousQuitDate(?\DateTimeInterface $d): static { $this->previousQuitDate = $d; return $this; }
    public function getNotes(): ?string { return $this->notes; }
    public function setNotes(?string $n): static { $this->notes = $n; return $this; }
    public function getMoneySavedAtRelapse(): ?float { return $this->moneySavedAtRelapse !== null ? (float) $this->moneySavedAtRelapse : null; }
    public function setMoneySavedAtRelapse(?float $m): static { $this->moneySavedAtRelapse = $m !== null ? (string) $m : null; return $this; }

    public function getPreviousStreakDays(): int
    {
        if (!$this->previousQuitDate || !$this->relapsedAt) return 0;
        return max(0, (int) $this->relapsedAt->diff($this->previousQuitDate)->days);
    }
}
