<?php

namespace App\Service;

use App\Entity\Prestamo;
use App\Entity\Usuario;
use App\Entity\Libro;
use App\Repository\PrestamoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * Service Layer para la gestión de préstamos.
 *
 * Este servicio implementa el patrón Service Layer que encapsula toda la lógica
 * de negocio relacionada con los préstamos, separándola de los controladores
 * y entidades. Esto permite:
 *
 * 1. Separación de responsabilidades (SRP - SOLID)
 * 2. Facilidad de testing (se puede mockear el servicio)
 * 3. Reutilización de la lógica desde múltiples puntos de entrada
 * 4. Centralización del manejo de errores y logging
 */
class PrestamoService
{
    private const MAX_PRESTAMOS_ACTIVOS = 3;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PrestamoRepository $prestamoRepository,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Crea un nuevo préstamo validando las reglas de negocio.
     *
     * @throws UnprocessableEntityHttpException Si el usuario excede el límite de préstamos
     * @throws ConflictHttpException Si el libro ya está prestado
     */
    public function crearPrestamo(Prestamo $prestamo): Prestamo
    {
        $usuario = $prestamo->getUsuario();
        $libro = $prestamo->getLibro();

        // Validar que el usuario no exceda el límite de préstamos activos
        $this->validarLimitePrestamos($usuario);

        // Validar que el libro esté disponible
        $this->validarDisponibilidadLibro($libro);

        // Establecer fecha de préstamo si no está definida
        if ($prestamo->getFechaPrestamo() === null) {
            $prestamo->setFechaPrestamo(new \DateTime());
        }

        $this->entityManager->persist($prestamo);
        $this->entityManager->flush();

        $this->logger->info('Préstamo creado', [
            'prestamo_id' => $prestamo->getId(),
            'usuario_id' => $usuario->getId(),
            'usuario_dni' => $usuario->getDni(),
            'libro_id' => $libro->getId(),
            'libro_isbn' => $libro->getIsbn(),
            'fecha_prestamo' => $prestamo->getFechaPrestamo()->format('Y-m-d H:i:s'),
        ]);

        return $prestamo;
    }

    /**
     * Devuelve un libro prestado.
     *
     * @throws UnprocessableEntityHttpException Si el préstamo ya fue devuelto
     */
    public function devolverLibro(Prestamo $prestamo): Prestamo
    {
        if (!$prestamo->estaActivo()) {
            throw new UnprocessableEntityHttpException(
                'Este préstamo ya fue devuelto el ' .
                $prestamo->getFechaDevolucion()->format('d/m/Y H:i')
            );
        }

        $prestamo->devolver();
        $this->entityManager->flush();

        $this->logger->info('Libro devuelto', [
            'prestamo_id' => $prestamo->getId(),
            'usuario_id' => $prestamo->getUsuario()->getId(),
            'libro_id' => $prestamo->getLibro()->getId(),
            'fecha_devolucion' => $prestamo->getFechaDevolucion()->format('Y-m-d H:i:s'),
        ]);

        return $prestamo;
    }

    /**
     * Obtiene estadísticas de préstamos por usuario en un rango de fechas.
     *
     * @return array<array{usuarioId: int, nombre: string, apellidos: string, dni: string, totalPrestamos: int}>
     */
    public function getEstadisticasPorRango(\DateTimeInterface $desde, \DateTimeInterface $hasta): array
    {
        $this->logger->debug('Consultando estadísticas de préstamos', [
            'desde' => $desde->format('Y-m-d'),
            'hasta' => $hasta->format('Y-m-d'),
        ]);

        return $this->prestamoRepository->getEstadisticasPorUsuarioEnRango($desde, $hasta);
    }

    /**
     * Cuenta los préstamos activos de un usuario.
     */
    public function countPrestamosActivos(Usuario $usuario): int
    {
        return $this->prestamoRepository->countPrestamosActivosByUsuario($usuario);
    }

    /**
     * Verifica si un usuario puede realizar más préstamos.
     */
    public function puedeRealizarPrestamo(Usuario $usuario): bool
    {
        return $this->countPrestamosActivos($usuario) < self::MAX_PRESTAMOS_ACTIVOS;
    }

    /**
     * Valida que el usuario no exceda el límite de préstamos activos.
     *
     * @throws UnprocessableEntityHttpException
     */
    private function validarLimitePrestamos(Usuario $usuario): void
    {
        $prestamosActivos = $this->countPrestamosActivos($usuario);

        if ($prestamosActivos >= self::MAX_PRESTAMOS_ACTIVOS) {
            $this->logger->warning('Usuario excede límite de préstamos', [
                'usuario_id' => $usuario->getId(),
                'usuario_dni' => $usuario->getDni(),
                'prestamos_activos' => $prestamosActivos,
                'limite' => self::MAX_PRESTAMOS_ACTIVOS,
            ]);

            throw new UnprocessableEntityHttpException(sprintf(
                'El usuario %s %s ya tiene %d préstamos activos. ' .
                'El máximo permitido es %d. Debe devolver algún libro antes de realizar un nuevo préstamo.',
                $usuario->getNombre(),
                $usuario->getApellidos(),
                $prestamosActivos,
                self::MAX_PRESTAMOS_ACTIVOS
            ));
        }
    }

    /**
     * Valida que el libro esté disponible para préstamo.
     *
     * @throws ConflictHttpException
     */
    private function validarDisponibilidadLibro(Libro $libro): void
    {
        if ($this->prestamoRepository->isLibroPrestado($libro)) {
            $this->logger->warning('Intento de préstamo de libro no disponible', [
                'libro_id' => $libro->getId(),
                'libro_isbn' => $libro->getIsbn(),
                'libro_titulo' => $libro->getTitulo(),
            ]);

            throw new ConflictHttpException(sprintf(
                'El libro "%s" (ISBN: %s) no está disponible actualmente. Ya se encuentra prestado.',
                $libro->getTitulo(),
                $libro->getIsbn()
            ));
        }
    }
}
