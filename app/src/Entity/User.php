<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[ORM\HasLifecycleCallbacks]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 20)]
    private string $addictionType = 'both';

    // Legacy fallback — kept for backward compat
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $quitDate = null;

    // Per-track quit dates
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $alcoholQuitDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $cigarettesQuitDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $cannabisQuitDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(options: ['default' => 0])]
    private int $totalXp = 0;

    #[ORM\Column(options: ['default' => 0])]
    private int $cravingsSurvived = 0;

    #[ORM\Column(options: ['default' => 0])]
    private int $dailyMessageCount = 0;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dailyMessageDate = null;

    // Legacy cost fallback
    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 2, nullable: true)]
    private ?string $dailyCost = null;

    // Per-track costs
    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 2, nullable: true)]
    private ?string $alcoholDailyCost = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 2, nullable: true)]
    private ?string $cigarettesDailyCost = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 2, nullable: true)]
    private ?string $cannabisDailyCost = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $currency = 'USD';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $motivation = null;

    #[ORM\Column(length: 50, options: ['default' => 'UTC'])]
    private string $timezone = 'UTC';

    #[ORM\Column(length: 30, nullable: true, unique: true)]
    private ?string $forumUsername = null;

    #[ORM\OneToMany(targetEntity: CheckIn::class, mappedBy: 'user', cascade: ['remove'])]
    private Collection $checkIns;

    #[ORM\OneToMany(targetEntity: JournalEntry::class, mappedBy: 'user', cascade: ['remove'])]
    private Collection $journalEntries;

    #[ORM\OneToMany(targetEntity: UserAchievement::class, mappedBy: 'user', cascade: ['remove'])]
    private Collection $userAchievements;

    #[ORM\OneToMany(targetEntity: CravingSession::class, mappedBy: 'user', cascade: ['remove'])]
    private Collection $cravingSessions;

    #[ORM\OneToMany(targetEntity: RelapseLog::class, mappedBy: 'user', cascade: ['remove'])]
    private Collection $relapseLogs;

    public function __construct()
    {
        $this->checkIns = new ArrayCollection();
        $this->journalEntries = new ArrayCollection();
        $this->userAchievements = new ArrayCollection();
        $this->cravingSessions = new ArrayCollection();
        $this->relapseLogs = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): static { $this->email = $email; return $this; }

    public function getName(): ?string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getUserIdentifier(): string { return (string) $this->email; }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }
    public function setRoles(array $roles): static { $this->roles = $roles; return $this; }

    public function getPassword(): ?string { return $this->password; }
    public function setPassword(string $password): static { $this->password = $password; return $this; }

    public function eraseCredentials(): void {}

    public function getAddictionType(): string { return $this->addictionType; }
    public function setAddictionType(string $t): static { $this->addictionType = $t; return $this; }

    // Legacy
    public function getQuitDate(): ?\DateTimeInterface { return $this->quitDate; }
    public function setQuitDate(?\DateTimeInterface $d): static { $this->quitDate = $d; return $this; }

    // Per-track
    public function getAlcoholQuitDate(): ?\DateTimeInterface { return $this->alcoholQuitDate; }
    public function setAlcoholQuitDate(?\DateTimeInterface $d): static { $this->alcoholQuitDate = $d; return $this; }

    public function getCigarettesQuitDate(): ?\DateTimeInterface { return $this->cigarettesQuitDate; }
    public function setCigarettesQuitDate(?\DateTimeInterface $d): static { $this->cigarettesQuitDate = $d; return $this; }

    public function getCannabisQuitDate(): ?\DateTimeInterface { return $this->cannabisQuitDate; }
    public function setCannabisQuitDate(?\DateTimeInterface $d): static { $this->cannabisQuitDate = $d; return $this; }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }

    public function getTotalXp(): int { return $this->totalXp; }
    public function addXp(int $xp): static { $this->totalXp += $xp; return $this; }

    public function getCravingsSurvived(): int { return $this->cravingsSurvived; }
    public function incrementCravingsSurvived(): static { $this->cravingsSurvived++; return $this; }

    public function getDailyMessageCount(): int { return $this->dailyMessageCount; }

    public function checkAndIncrementDailyMessages(int $limit = 50): bool
    {
        $today = new \DateTime('today');
        if ($this->dailyMessageDate === null || $this->dailyMessageDate->format('Y-m-d') !== $today->format('Y-m-d')) {
            $this->dailyMessageCount = 0;
            $this->dailyMessageDate = $today;
        }
        if ($this->dailyMessageCount >= $limit) {
            return false;
        }
        $this->dailyMessageCount++;
        return true;
    }

    public function getDailyCost(): ?string { return $this->dailyCost; }
    public function setDailyCost(?string $c): static { $this->dailyCost = $c; return $this; }

    public function getAlcoholDailyCost(): ?string { return $this->alcoholDailyCost; }
    public function setAlcoholDailyCost(?string $c): static { $this->alcoholDailyCost = $c; return $this; }

    public function getCigarettesDailyCost(): ?string { return $this->cigarettesDailyCost; }
    public function setCigarettesDailyCost(?string $c): static { $this->cigarettesDailyCost = $c; return $this; }

    public function getCannabisDailyCost(): ?string { return $this->cannabisDailyCost; }
    public function setCannabisDailyCost(?string $c): static { $this->cannabisDailyCost = $c; return $this; }

    public function getCurrency(): ?string { return $this->currency; }
    public function setCurrency(?string $c): static { $this->currency = $c; return $this; }

    public function getMotivation(): ?string { return $this->motivation; }
    public function setMotivation(?string $m): static { $this->motivation = $m; return $this; }

    public function getTimezone(): string { return $this->timezone ?: 'UTC'; }
    public function setTimezone(string $tz): static { $this->timezone = $tz; return $this; }

    public function getForumUsername(): ?string { return $this->forumUsername; }
    public function setForumUsername(?string $u): static { $this->forumUsername = $u; return $this; }

    public function getCheckIns(): Collection { return $this->checkIns; }
    public function getJournalEntries(): Collection { return $this->journalEntries; }
    public function getUserAchievements(): Collection { return $this->userAchievements; }
    public function getCravingSessions(): Collection { return $this->cravingSessions; }
    public function getRelapseLogs(): Collection { return $this->relapseLogs; }

    /**
     * Get the quit date for a specific addiction type.
     * Falls back to legacy quitDate if per-track date not set.
     */
    public function getQuitDateFor(string $type): ?\DateTimeInterface
    {
        return match($type) {
            'alcohol'    => $this->alcoholQuitDate ?? $this->quitDate,
            'cigarettes' => $this->cigarettesQuitDate ?? $this->quitDate,
            'cannabis'   => $this->cannabisQuitDate,
            default      => $this->alcoholQuitDate ?? $this->cigarettesQuitDate ?? $this->quitDate,
        };
    }

    public function getDaysSinceQuit(?string $type = null): int
    {
        if ($type === null) {
            $type = $this->addictionType === 'both' ? 'longest' : $this->addictionType;
        }

        if ($type === 'longest') {
            $days = 0;
            if ($this->addictionType !== 'cigarettes' && $this->addictionType !== 'cannabis') {
                $days = max($days, $this->getDaysSinceQuit('alcohol'));
            }
            if ($this->addictionType !== 'alcohol' && $this->addictionType !== 'cannabis') {
                $days = max($days, $this->getDaysSinceQuit('cigarettes'));
            }
            if ($this->addictionType === 'cannabis') {
                $days = max($days, $this->getDaysSinceQuit('cannabis'));
            }
            return $days;
        }

        $date = $this->getQuitDateFor($type);
        if (!$date) return 0;
        return max(0, (int) (new \DateTime())->diff($date)->days);
    }

    public function getHoursSinceQuit(?string $type = null): float
    {
        if ($type === null) {
            $type = $this->addictionType === 'both' ? 'longest' : $this->addictionType;
        }

        if ($type === 'longest') {
            $hours = 0.0;
            if ($this->addictionType !== 'cigarettes' && $this->addictionType !== 'cannabis') {
                $hours = max($hours, $this->getHoursSinceQuit('alcohol'));
            }
            if ($this->addictionType !== 'alcohol' && $this->addictionType !== 'cannabis') {
                $hours = max($hours, $this->getHoursSinceQuit('cigarettes'));
            }
            if ($this->addictionType === 'cannabis') {
                $hours = max($hours, $this->getHoursSinceQuit('cannabis'));
            }
            return $hours;
        }

        $date = $this->getQuitDateFor($type);
        if (!$date) return 0.0;
        $diff = (new \DateTime())->getTimestamp() - $date->getTimestamp();
        return max(0.0, $diff / 3600);
    }

    public function getLevel(): int
    {
        $days = $this->getDaysSinceQuit();
        return match(true) {
            $days >= 365 => 7,
            $days >= 180 => 6,
            $days >= 90  => 5,
            $days >= 30  => 4,
            $days >= 7   => 3,
            $days >= 1   => 2,
            default      => 1,
        };
    }

    public function getLevelName(): string
    {
        return match($this->getLevel()) {
            7 => 'Beacon of Light',
            6 => 'Transformed',
            5 => 'Conqueror',
            4 => 'Building Momentum',
            3 => 'Finding Ground',
            2 => 'Breaking Free',
            default => 'Awakening',
        };
    }

    public function getXpForNextLevel(): int
    {
        return match($this->getLevel()) {
            1 => 100,
            2 => 300,
            3 => 700,
            4 => 1500,
            5 => 3000,
            6 => 6000,
            default => 99999,
        };
    }

    public function getMoneySaved(?string $type = null): float
    {
        if ($type === null) $type = $this->addictionType === 'both' ? 'total' : $this->addictionType;

        if ($type === 'total') {
            return $this->getMoneySaved('alcohol') + $this->getMoneySaved('cigarettes');
        }

        $cost = match($type) {
            'alcohol'    => $this->alcoholDailyCost ?? $this->dailyCost,
            'cigarettes' => $this->cigarettesDailyCost ?? $this->dailyCost,
            'cannabis'   => $this->cannabisDailyCost,
            default      => null,
        };

        if (!$cost) return 0.0;
        return (float) $cost * $this->getDaysSinceQuit($type);
    }

    public function getRelapseCount(?string $type = null): int
    {
        $logs = $this->relapseLogs->filter(fn(RelapseLog $r) =>
            $type === null || $r->getAddictionType() === $type
        );
        return $logs->count();
    }
}
