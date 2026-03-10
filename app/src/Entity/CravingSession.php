<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class CravingSession
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'cravingSessions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $startedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $endedAt = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $outcome = null; // survived, relapsed, abandoned

    #[ORM\Column(length: 20)]
    private string $addictionType = 'alcohol';

    #[ORM\OneToMany(targetEntity: ChatMessage::class, mappedBy: 'session', cascade: ['remove'])]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    private Collection $messages;

    public function __construct()
    {
        $this->messages = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->startedAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }
    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $u): static { $this->user = $u; return $this; }
    public function getStartedAt(): ?\DateTimeInterface { return $this->startedAt; }
    public function getEndedAt(): ?\DateTimeInterface { return $this->endedAt; }
    public function setEndedAt(?\DateTimeInterface $e): static { $this->endedAt = $e; return $this; }
    public function getOutcome(): ?string { return $this->outcome; }
    public function setOutcome(?string $o): static { $this->outcome = $o; return $this; }
    public function getAddictionType(): string { return $this->addictionType; }
    public function setAddictionType(string $t): static { $this->addictionType = $t; return $this; }
    public function getMessages(): Collection { return $this->messages; }
}
