<?php

namespace App\Entity;

use App\Repository\PushSubscriptionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PushSubscriptionRepository::class)]
#[ORM\UniqueConstraint(columns: ['endpoint'])]
class PushSubscription
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(type: 'text')]
    private string $endpoint = '';

    #[ORM\Column(length: 255)]
    private string $p256dh = '';

    #[ORM\Column(length: 255)]
    private string $auth = '';

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $lastNotifiedAt = null;

    public function getId(): ?int { return $this->id; }
    public function getUser(): ?User { return $this->user; }
    public function setUser(User $u): static { $this->user = $u; return $this; }
    public function getEndpoint(): string { return $this->endpoint; }
    public function setEndpoint(string $e): static { $this->endpoint = $e; return $this; }
    public function getP256dh(): string { return $this->p256dh; }
    public function setP256dh(string $k): static { $this->p256dh = $k; return $this; }
    public function getAuth(): string { return $this->auth; }
    public function setAuth(string $a): static { $this->auth = $a; return $this; }
    public function getLastNotifiedAt(): ?\DateTimeInterface { return $this->lastNotifiedAt; }
    public function setLastNotifiedAt(\DateTimeInterface $d): static { $this->lastNotifiedAt = $d; return $this; }
}
