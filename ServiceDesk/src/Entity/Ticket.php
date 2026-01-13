<?php
declare(strict_types=1);

namespace App\Entity;

use App\Repository\TicketRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TicketRepository::class)]
#[ORM\Table(name: 'ticket')]
#[ORM\Index(name: 'idx_ticket_status', columns: ['status'])]
#[ORM\HasLifecycleCallbacks]
class Ticket
{
    public const STATUS_NEW = 'NEW';
    public const STATUS_IN_PROGRESS = 'IN_PROGRESS';
    public const STATUS_RESOLVED = 'RESOLVED';
    public const STATUS_CLOSED = 'CLOSED';

    public const PRIORITY_LOW = 'LOW';
    public const PRIORITY_MEDIUM = 'MEDIUM';
    public const PRIORITY_HIGH = 'HIGH';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $title = '';

    #[ORM\Column(type: 'text')]
    private string $description = '';

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = self::STATUS_NEW;

    #[ORM\Column(type: 'string', length: 10)]
    private string $priority = self::PRIORITY_MEDIUM;

    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'tickets')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Category $category = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $author = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $assignedTo = null;

    #[ORM\OneToMany(mappedBy: 'ticket', targetEntity: Comment::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    private Collection $comments;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
        $this->status = self::STATUS_NEW;
        $this->priority = self::PRIORITY_MEDIUM;
    }

    public function __toString(): string
    {
        $id = $this->id ?? 'new';
        $t = $this->title ?: 'Ticket';
        return sprintf('#%s %s', $id, $t);
    }

    public static function allowedStatuses(): array
    {
        return [self::STATUS_NEW, self::STATUS_IN_PROGRESS, self::STATUS_RESOLVED, self::STATUS_CLOSED];
    }

    public static function allowedPriorities(): array
    {
        return [self::PRIORITY_LOW, self::PRIORITY_MEDIUM, self::PRIORITY_HIGH];
    }

    public function getId(): ?int { return $this->id; }

    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): self
    {
        $this->title = trim($title);
        return $this;
    }

    public function getDescription(): string { return $this->description; }
    public function setDescription(string $description): self
    {
        $this->description = trim($description);
        return $this;
    }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self
    {
        $status = strtoupper(trim($status));
        if (!in_array($status, self::allowedStatuses(), true)) {
            $status = self::STATUS_NEW; // безопасный fallback
        }
        $this->status = $status;
        return $this;
    }

    public function getPriority(): string { return $this->priority; }
    public function setPriority(string $priority): self
    {
        $priority = strtoupper(trim($priority));
        if (!in_array($priority, self::allowedPriorities(), true)) {
            $priority = self::PRIORITY_MEDIUM;
        }
        $this->priority = $priority;
        return $this;
    }

    public function getCategory(): ?Category { return $this->category; }
    public function setCategory(?Category $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function getAuthor(): ?User { return $this->author; }
    public function setAuthor(User $author): self
    {
        $this->author = $author;
        return $this;
    }

    public function getAssignedTo(): ?User { return $this->assignedTo; }
    public function setAssignedTo(?User $assignedTo): self
    {
        $this->assignedTo = $assignedTo;
        return $this;
    }

    /** @return Collection<int, Comment> */
    public function getComments(): Collection { return $this->comments; }

    public function addComment(Comment $comment): self
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setTicket($this);
        }
        return $this;
    }

    public function removeComment(Comment $comment): self
    {
        if ($this->comments->removeElement($comment)) {
            if ($comment->getTicket() === $this) {
                $comment->setTicket(null);
            }
        }
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $now = new \DateTimeImmutable();
        $this->createdAt ??= $now;
        $this->updatedAt ??= $now;

        // Нормализуем значения при первом сохранении
        $this->setStatus($this->status);
        $this->setPriority($this->priority);
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
        $this->setStatus($this->status);
        $this->setPriority($this->priority);
    }
}
