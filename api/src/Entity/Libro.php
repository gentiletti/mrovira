<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use App\Repository\LibroRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LibroRepository::class)]
#[ORM\Table(name: 'libros')]
#[UniqueEntity(fields: ['isbn'], message: 'Ya existe un libro con este ISBN')]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(),
        new Put(),
        new Delete(),
    ],
    normalizationContext: ['groups' => ['libro:read']],
    denormalizationContext: ['groups' => ['libro:write']],
    paginationEnabled: true,
    paginationItemsPerPage: 10
)]
#[ApiFilter(SearchFilter::class, properties: ['titulo' => 'partial', 'autor' => 'partial', 'isbn' => 'exact'])]
class Libro
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['libro:read', 'prestamo:read'])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'El título es obligatorio')]
    #[Assert\Length(max: 255, maxMessage: 'El título no puede tener más de {{ limit }} caracteres')]
    #[Groups(['libro:read', 'libro:write', 'prestamo:read'])]
    private ?string $titulo = null;

    #[ORM\Column(type: 'string', length: 200)]
    #[Assert\NotBlank(message: 'El autor es obligatorio')]
    #[Assert\Length(max: 200, maxMessage: 'El autor no puede tener más de {{ limit }} caracteres')]
    #[Groups(['libro:read', 'libro:write', 'prestamo:read'])]
    private ?string $autor = null;

    #[ORM\Column(type: 'string', length: 20, unique: true)]
    #[Assert\NotBlank(message: 'El ISBN es obligatorio')]
    #[Assert\Length(max: 20, maxMessage: 'El ISBN no puede tener más de {{ limit }} caracteres')]
    #[Groups(['libro:read', 'libro:write'])]
    private ?string $isbn = null;

    /**
     * @var Collection<int, Prestamo>
     */
    #[ORM\OneToMany(mappedBy: 'libro', targetEntity: Prestamo::class, orphanRemoval: true)]
    private Collection $prestamos;

    public function __construct()
    {
        $this->prestamos = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitulo(): ?string
    {
        return $this->titulo;
    }

    public function setTitulo(string $titulo): static
    {
        $this->titulo = $titulo;
        return $this;
    }

    public function getAutor(): ?string
    {
        return $this->autor;
    }

    public function setAutor(string $autor): static
    {
        $this->autor = $autor;
        return $this;
    }

    public function getIsbn(): ?string
    {
        return $this->isbn;
    }

    public function setIsbn(string $isbn): static
    {
        $this->isbn = $isbn;
        return $this;
    }

    /**
     * @return Collection<int, Prestamo>
     */
    public function getPrestamos(): Collection
    {
        return $this->prestamos;
    }

    public function addPrestamo(Prestamo $prestamo): static
    {
        if (!$this->prestamos->contains($prestamo)) {
            $this->prestamos->add($prestamo);
            $prestamo->setLibro($this);
        }
        return $this;
    }

    public function removePrestamo(Prestamo $prestamo): static
    {
        if ($this->prestamos->removeElement($prestamo)) {
            if ($prestamo->getLibro() === $this) {
                $prestamo->setLibro(null);
            }
        }
        return $this;
    }

    /**
     * Verifica si el libro está actualmente prestado
     */
    public function estaPrestado(): bool
    {
        foreach ($this->prestamos as $prestamo) {
            if ($prestamo->getFechaDevolucion() === null) {
                return true;
            }
        }
        return false;
    }
}
