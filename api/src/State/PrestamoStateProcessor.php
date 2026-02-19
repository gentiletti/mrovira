<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Prestamo;
use App\Service\PrestamoService;

/**
 * State Processor para interceptar la creación de préstamos.
 *
 * Este processor se ejecuta antes de persistir un nuevo préstamo,
 * delegando la validación y creación al PrestamoService.
 */
class PrestamoStateProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly PrestamoService $prestamoService
    ) {
    }

    /**
     * @param Prestamo $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Prestamo
    {
        return $this->prestamoService->crearPrestamo($data);
    }
}
