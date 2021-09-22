<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use App\Repository\CommentRepository;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=CommentRepository::class)
 * @ORM\HasLifecycleCallbacks()
 *
 * @ApiResource(
 *     collectionOperations={"get"={"normalization_context"={"groups"="comment:list"}}},
 *     itemOperations={"get"={"normalization_context"={"groups"="comment:item"}}},
 *     order={"year"="DESC", "city"="ASC"},
 *     paginationEnabled=false
 * )
 *
 * @ApiFilter(SearchFilter::class, properties={"conference": "exact"})
 */
class Comment
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    #[Groups(['comment:list', 'comment:item'])]
    private int $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    #[Assert\NotBlank]
    #[Groups(['comment:list', 'comment:item'])]
    private ?string $author;

    /**
     * @ORM\Column(type="text")
     */
    #[Assert\NotBlank]
    #[Groups(['comment:list', 'comment:item'])]
    private ?string $text;

    /**
     * @ORM\Column(type="string", length=255)
     */
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Groups(['comment:list', 'comment:item'])]
    private ?string $email;

    /**
     * @ORM\Column(type="datetime")
     */
    #[Groups(['comment:list', 'comment:item'])]
    private ?DateTimeInterface $createdAt;

    /**
     * @ORM\ManyToOne(targetEntity=Conference::class, inversedBy="comments")
     * @ORM\JoinColumn(nullable=false)
     */
    #[Groups(['comment:list', 'comment:item'])]
    private ?Conference $conference;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    #[Groups(['comment:list', 'comment:item'])]
    private ?string $photoFilename;

    /**
     * @ORM\Column(type="string", length=255), options={"default": "submitted"}
     */
    #[Groups(['comment:list', 'comment:item'])]
    private string $state = 'submitted';

    /**
     * @return string
     */
    public function __toString() : string
    {
        return (string) $this->getEmail();
    }

    /**
     * @return int|null
     */
    public function getId() : ?int
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getAuthor() : ?string
    {
        return $this->author;
    }

    /**
     * @param string $author
     *
     * @return $this
     */
    public function setAuthor(string $author) : self
    {
        $this->author = $author;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getText() : ?string
    {
        return $this->text;
    }

    /**
     * @param string $text
     *
     * @return $this
     */
    public function setText(string $text) : self
    {
        $this->text = $text;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getEmail() : ?string
    {
        return $this->email;
    }

    public function setEmail(string $email) : self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return DateTimeInterface|null
     */
    public function getCreatedAt() : ?DateTimeInterface
    {
        return $this->createdAt;
    }

    /**
     * @param DateTimeInterface $createdAt
     *
     * @return $this
     */
    public function setCreatedAt(DateTimeInterface $createdAt) : self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @ORM\PrePersist
     */
    public function setCreatedAtValue()
    {
        $this->createdAt = new \DateTime();
    }

    /**
     * @return Conference|null
     */
    public function getConference() : ?Conference
    {
        return $this->conference;
    }

    /**
     * @param Conference|null $conference
     *
     * @return $this
     */
    public function setConference(?Conference $conference) : self
    {
        $this->conference = $conference;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPhotoFilename() : ?string
    {
        return $this->photoFilename;
    }

    /**
     * @param string|null $photoFilename
     *
     * @return $this
     */
    public function setPhotoFilename(?string $photoFilename) : self
    {
        $this->photoFilename = $photoFilename;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getState() : ?string
    {
        return $this->state;
    }

    /**
     * @param string $state
     *
     * @return $this
     */
    public function setState(string $state) : self
    {
        $this->state = $state;

        return $this;
    }
}
