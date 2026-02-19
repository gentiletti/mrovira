<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use App\Repository\UsuarioRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UsuarioRepository::class)]
#[ORM\Table(name: 'usuarios')]
#[UniqueEntity(fields: ['dni'], message: 'Ya existe un usuario con este DNI')]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(),
        new Put(),
        new Delete(),
    ],
    normalizationContext: ['groups' => ['usuario:read']],
    denormalizationContext: ['groups' => ['usuario:write']],
    paginationEnabled: true,
    paginationItemsPerPage: 10
)]
#[ApiFilter(SearchFilter::class, properties: ['nombre' => 'partial', 'apellidos' => 'partial', 'dni' => 'exact'])]
class Usuario
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['usuario:read', 'prestamo:read'])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank(message: 'El nombre es obligatorio')]
    #[Assert\Length(max: 100, maxMessage: 'El nombre no puede tener más de {{ limit }} caracteres')]
    #[Groups(['usuario:read', 'usuario:write', 'prestamo:read'])]
    private ?string $nombre = null;

    #[ORM\Column(type: 'string', length: 150)]
    #[Assert\NotBlank(message: 'Los apellidos son obligatorios')]
    #[Assert\Length(max: 150, maxMessage: 'Los apellidos no pueden tener más de {{ limit }} caracteres')]
    #[Groups(['usuario:read', 'usuario:write', 'prestamo:read'])]
    private ?string $apellidos = null;

    #[ORM\Column(type: 'string', length: 20, unique: true)]
    #[Assert\NotBlank(message: 'El DNI es obligatorio')]
    #[Assert\Length(max: 20, maxMessage: 'El DNI no puede tener más de {{ limit }} caracteres')]
    #[Groups(['usuario:read', 'usuario:write'])]
    private ?string $dni = null;

    /**
     * @var Collection<int, Prestamo>
     */
    #[ORM\OneToMany(mappedBy: 'usuario', targetEntity: Prestamo::class, orphanRemoval: true)]
    #[Groups(['usuario:read'])]
    private Collection $prestamos;

    public function __construct()
    {
        $this->prestamos = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNombre(): ?string
    {
        return $this->nombre;
    }

    public function setNombre(string $nombre): static
    {
        $this->nombre = $nombre;
        return $this;
    }

    public function getApellidos(): ?string
    {
        return $this->apellidos;
    }

    public function setApellidos(string $apellidos): static
    {
        $this->apellidos = $apellidos;
        return $this;
    }

    public function getDni(): ?string
    {
        return $this->dni;
    }

    public function setDni(string $dni): static
    {
        $this->dni = $dni;
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
            $prestamo->setUsuario($this);
        }
        return $this;
    }

    public function removePrestamo(Prestamo $prestamo): static
    {
        if ($this->prestamos->removeElement($prestamo)) {
            if ($prestamo->getUsuario() === $this) {
                $prestamo->setUsuario(null);
            }
        }
        return $this;
    }

    /**
     * Cuenta los préstamos activos (sin fecha de devolución)
     */
    public function getPrestamosActivosCount(): int
    {
        return $this->prestamos->filter(
            fn(Prestamo $prestamo) => $prestamo->getFechaDevolucion() === null
        )->count();
    }

    /**
     * Verifica si el usuario puede realizar más préstamos
     */
    public function puedeRealizarPrestamo(): bool
    {
        return $this->getPrestamosActivosCount() < 3;
    }
}
