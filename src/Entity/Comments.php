<?php

namespace App\Entity;

use App\Repository\CommentsRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: CommentsRepository::class)]
class Comments
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['comment'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'comments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Post $article = null;

    #[ORM\Column(length: 255)]
    #[Groups(['comment'])]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Groups(['comment'])]
    private ?string $mail = null;

    #[ORM\Column(length: 255)]
    #[Groups(['comment'])]
    private ?string $url = null;

    #[ORM\Column(length: 255)]
    #[Groups(['comment'])]
    private ?string $text = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getArticle(): ?Post
    {
        return $this->article;
    }

    public function setArticle(?Post $article): static
    {
        $this->article = $article;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getMail(): ?string
    {
        return $this->mail;
    }

    public function setMail(string $mail): static
    {
        $this->mail = $mail;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(string $text): static
    {
        $this->text = $text;

        return $this;
    }
}
