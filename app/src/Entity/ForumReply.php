<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'forum_reply')]
#[ORM\HasLifecycleCallbacks]
class ForumReply
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ForumPost::class, inversedBy: 'replies')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?ForumPost $post = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(type: Types::TEXT)]
    private string $body = '';

    #[ORM\Column(options: ['default' => false])]
    private bool $isFlagged = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }
    public function getPost(): ?ForumPost { return $this->post; }
    public function setPost(?ForumPost $p): static { $this->post = $p; return $this; }
    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $u): static { $this->user = $u; return $this; }
    public function getBody(): string { return $this->body; }
    public function setBody(string $b): static { $this->body = $b; return $this; }
    public function isFlagged(): bool { return $this->isFlagged; }
    public function setIsFlagged(bool $f): static { $this->isFlagged = $f; return $this; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
}
