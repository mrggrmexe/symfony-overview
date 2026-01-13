<?php
declare(strict_types=1);

namespace App\Entity;

use App\Repository\CommentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommentRepository::class)]
#[ORM\Table(name: 'comment')]
#[ORM\Index(name: 'idx_comment_ticket', columns: ['ticket_id'])]
#[ORM\HasLifecycleCallbacks]
class Comment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Ticket::class, inversedBy: 'comments')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Ticket $ticket = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $author = null;

    #[ORM\Column(type: 'text')]
    private string $message = '';

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    public function __toString(): string
    {
        $id = $this->id ?? 'new';
        $msg = trim($this->message);
        $msg = $msg !== '' ? mb_substr($msg, 0, 30) : 'Comment';
        return sprintf('#%s %s', $id, $msg);
    }

    public function getId(): ?int { return $this->id; }

    public function getTicket(): ?Ticket { return $this->ticket; }
    public function setTicket(?Ticket $ticket): self
    {
        $this->ticket = $ticket;
        return $this;
    }

    public function getAuthor(): ?User { return $this->author; }
    public function setAuthor(User $author): self
    {
        $this->author = $author;
        return $this;
    }

    public function getMessage(): string { return $this->message; }
    public function setMessage(string $message): self
    {
        $this->message = trim($message);
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt ??= new \DateTimeImmutable();
    }
}
