<?php

namespace App\Repository;

use App\Entity\Prestamo;
use App\Entity\Usuario;
use App\Entity\Libro;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Prestamo>
 */
class PrestamoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Prestamo::class);
    }

    /**
     * Cuenta los préstamos activos de un usuario
     */
    public function countPrestamosActivosByUsuario(Usuario $usuario): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.usuario = :usuario')
            ->andWhere('p.fechaDevolucion IS NULL')
            ->setParameter('usuario', $usuario)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Verifica si un libro está actualmente prestado
     */
    public function isLibroPrestado(Libro $libro): bool
    {
        $count = (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.libro = :libro')
            ->andWhere('p.fechaDevolucion IS NULL')
            ->setParameter('libro', $libro)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * Encuentra préstamos en un rango de fechas
     *
     * @return Prestamo[]
     */
    public function findByRangoFechas(\DateTimeInterface $desde, \DateTimeInterface $hasta): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.fechaPrestamo BETWEEN :desde AND :hasta')
            ->setParameter('desde', $desde)
            ->setParameter('hasta', $hasta)
            ->orderBy('p.fechaPrestamo', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Encuentra préstamos activos
     *
     * @return Prestamo[]
     */
    public function findActivos(): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.fechaDevolucion IS NULL')
            ->orderBy('p.fechaPrestamo', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Obtiene estadísticas de préstamos por usuario en un rango de fechas
     *
     * @return array<array{usuarioId: int, nombre: string, apellidos: string, totalPrestamos: int}>
     */
    public function getEstadisticasPorUsuarioEnRango(\DateTimeInterface $desde, \DateTimeInterface $hasta): array
    {
        return $this->createQueryBuilder('p')
            ->select(
                'u.id as usuarioId',
                'u.nombre',
                'u.apellidos',
                'u.dni',
                'COUNT(p.id) as totalPrestamos'
            )
            ->join('p.usuario', 'u')
            ->where('p.fechaPrestamo BETWEEN :desde AND :hasta')
            ->setParameter('desde', $desde)
            ->setParameter('hasta', $hasta)
            ->groupBy('u.id', 'u.nombre', 'u.apellidos', 'u.dni')
            ->orderBy('totalPrestamos', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
