<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'forum_post')]
#[ORM\HasLifecycleCallbacks]
class ForumPost
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(length: 20)]
    private string $category = 'general';

    #[ORM\Column(length: 150)]
    private string $title = '';

    #[ORM\Column(type: Types::TEXT)]
    private string $body = '';

    #[ORM\Column(options: ['default' => false])]
    private bool $isFlagged = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\OneToMany(targetEntity: ForumReply::class, mappedBy: 'post', cascade: ['remove'])]
    private Collection $replies;

    public function __construct()
    {
        $this->replies = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }
    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $u): static { $this->user = $u; return $this; }
    public function getCategory(): string { return $this->category; }
    public function setCategory(string $c): static { $this->category = $c; return $this; }
    public function getTitle(): string { return $this->title; }
    public function setTitle(string $t): static { $this->title = $t; return $this; }
    public function getBody(): string { return $this->body; }
    public function setBody(string $b): static { $this->body = $b; return $this; }
    public function isFlagged(): bool { return $this->isFlagged; }
    public function setIsFlagged(bool $f): static { $this->isFlagged = $f; return $this; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getReplies(): Collection { return $this->replies; }

    public function getCategoryLabel(): string
    {
        return match($this->category) {
            'wins'     => '🏆 Wins',
            'support'  => '💙 Support',
            'feedback' => '📣 Feedback',
            default    => '💬 General',
        };
    }

    public function getCategoryColor(): string
    {
        return match($this->category) {
            'wins'     => 'bg-amber-100 text-amber-700',
            'support'  => 'bg-blue-100 text-blue-700',
            'feedback' => 'bg-purple-100 text-purple-700',
            default    => 'bg-gray-100 text-gray-600',
        };
    }
}
