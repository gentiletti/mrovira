<?php

namespace App\Repository;

use App\Entity\Libro;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Libro>
 */
class LibroRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Libro::class);
    }

    /**
     * Encuentra un libro por ISBN
     */
    public function findByIsbn(string $isbn): ?Libro
    {
        return $this->findOneBy(['isbn' => $isbn]);
    }

    /**
     * Verifica si un libro está disponible (no prestado actualmente)
     */
    public function estaDisponible(Libro $libro): bool
    {
        $count = (int) $this->createQueryBuilder('l')
            ->select('COUNT(p.id)')
            ->join('l.prestamos', 'p')
            ->where('l.id = :libroId')
            ->andWhere('p.fechaDevolucion IS NULL')
            ->setParameter('libroId', $libro->getId())
            ->getQuery()
            ->getSingleScalarResult();

        return $count === 0;
    }

    /**
     * Encuentra libros disponibles (no prestados)
     *
     * @return Libro[]
     */
    public function findDisponibles(): array
    {
        $subQuery = $this->getEntityManager()->createQueryBuilder()
            ->select('IDENTITY(p.libro)')
            ->from('App\Entity\Prestamo', 'p')
            ->where('p.fechaDevolucion IS NULL')
            ->getDQL();

        return $this->createQueryBuilder('l')
            ->where('l.id NOT IN (' . $subQuery . ')')
            ->orderBy('l.titulo', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
