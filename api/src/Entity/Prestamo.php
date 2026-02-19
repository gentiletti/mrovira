<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use App\Repository\PrestamoRepository;
use App\State\PrestamoStateProcessor;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PrestamoRepository::class)]
#[ORM\Table(name: 'prestamos')]
#[ORM\Index(columns: ['fecha_prestamo'], name: 'idx_fecha_prestamo')]
#[ORM\Index(columns: ['fecha_devolucion'], name: 'idx_fecha_devolucion')]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(processor: PrestamoStateProcessor::class),
        new Patch(),
        new Delete(),
    ],
    normalizationContext: ['groups' => ['prestamo:read']],
    denormalizationContext: ['groups' => ['prestamo:write']],
    paginationEnabled: true,
    paginationItemsPerPage: 10
)]
#[ApiFilter(DateFilter::class, properties: ['fechaPrestamo', 'fechaDevolucion'])]
#[ApiFilter(SearchFilter::class, properties: ['usuario.id' => 'exact', 'libro.id' => 'exact'])]
class Prestamo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['prestamo:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Usuario::class, inversedBy: 'prestamos')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'El usuario es obligatorio')]
    #[Groups(['prestamo:read', 'prestamo:write'])]
    private ?Usuario $usuario = null;

    #[ORM\ManyToOne(targetEntity: Libro::class, inversedBy: 'prestamos')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'El libro es obligatorio')]
    #[Groups(['prestamo:read', 'prestamo:write'])]
    private ?Libro $libro = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotNull(message: 'La fecha de préstamo es obligatoria')]
    #[Groups(['prestamo:read', 'prestamo:write'])]
    private ?\DateTimeInterface $fechaPrestamo = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['prestamo:read', 'prestamo:write'])]
    private ?\DateTimeInterface $fechaDevolucion = null;

    public function __construct()
    {
        $this->fechaPrestamo = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsuario(): ?Usuario
    {
        return $this->usuario;
    }

    public function setUsuario(?Usuario $usuario): static
    {
        $this->usuario = $usuario;
        return $this;
    }

    public function getLibro(): ?Libro
    {
        return $this->libro;
    }

    public function setLibro(?Libro $libro): static
    {
        $this->libro = $libro;
        return $this;
    }

    public function getFechaPrestamo(): ?\DateTimeInterface
    {
        return $this->fechaPrestamo;
    }

    public function setFechaPrestamo(\DateTimeInterface $fechaPrestamo): static
    {
        $this->fechaPrestamo = $fechaPrestamo;
        return $this;
    }

    public function getFechaDevolucion(): ?\DateTimeInterface
    {
        return $this->fechaDevolucion;
    }

    public function setFechaDevolucion(?\DateTimeInterface $fechaDevolucion): static
    {
        $this->fechaDevolucion = $fechaDevolucion;
        return $this;
    }

    /**
     * Verifica si el préstamo está activo (no devuelto)
     */
    public function estaActivo(): bool
    {
        return $this->fechaDevolucion === null;
    }

    /**
     * Devuelve el libro (establece la fecha de devolución)
     */
    public function devolver(): static
    {
        $this->fechaDevolucion = new \DateTime();
        return $this;
    }
}
