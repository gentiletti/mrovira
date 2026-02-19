<?php

namespace App\Repository;

use App\Entity\Usuario;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Usuario>
 */
class UsuarioRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Usuario::class);
    }

    /**
     * Encuentra usuarios con el conteo de préstamos en un rango de fechas
     *
     * @return array<array{usuario: Usuario, totalPrestamos: int}>
     */
    public function findUsuariosConPrestamosEnRango(\DateTimeInterface $desde, \DateTimeInterface $hasta): array
    {
        return $this->createQueryBuilder('u')
            ->select('u as usuario', 'COUNT(p.id) as totalPrestamos')
            ->leftJoin('u.prestamos', 'p', 'WITH', 'p.fechaPrestamo BETWEEN :desde AND :hasta')
            ->setParameter('desde', $desde)
            ->setParameter('hasta', $hasta)
            ->groupBy('u.id')
            ->having('COUNT(p.id) > 0')
            ->orderBy('totalPrestamos', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Cuenta los préstamos activos de un usuario
     */
    public function countPrestamosActivos(Usuario $usuario): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(p.id)')
            ->join('u.prestamos', 'p')
            ->where('u.id = :usuarioId')
            ->andWhere('p.fechaDevolucion IS NULL')
            ->setParameter('usuarioId', $usuario->getId())
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Encuentra un usuario por DNI
     */
    public function findByDni(string $dni): ?Usuario
    {
        return $this->findOneBy(['dni' => $dni]);
    }
}
