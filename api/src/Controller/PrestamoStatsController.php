<?php

namespace App\Controller;

use App\Service\PrestamoService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controlador para las estadísticas de préstamos.
 */
#[Route('/api/estadisticas')]
class PrestamoStatsController extends AbstractController
{
    public function __construct(
        private readonly PrestamoService $prestamoService
    ) {
    }

    /**
     * Obtiene estadísticas de préstamos por usuario en un rango de fechas.
     *
     * @example GET /api/estadisticas/prestamos?desde=2024-01-01&hasta=2024-12-31
     */
    #[Route('/prestamos', name: 'prestamos_estadisticas', methods: ['GET'])]
    public function estadisticas(Request $request): JsonResponse
    {
        $desdeStr = $request->query->get('desde');
        $hastaStr = $request->query->get('hasta');

        if (!$desdeStr || !$hastaStr) {
            return new JsonResponse([
                'error' => 'Los parámetros "desde" y "hasta" son obligatorios',
                'ejemplo' => '/api/prestamos/estadisticas?desde=2024-01-01&hasta=2024-12-31'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $desde = new \DateTime($desdeStr);
            $hasta = new \DateTime($hastaStr);

            // Ajustar 'hasta' para incluir todo el día
            $hasta->setTime(23, 59, 59);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Formato de fecha inválido. Use el formato YYYY-MM-DD',
                'detalles' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($desde > $hasta) {
            return new JsonResponse([
                'error' => 'La fecha "desde" no puede ser mayor que la fecha "hasta"'
            ], Response::HTTP_BAD_REQUEST);
        }

        $estadisticas = $this->prestamoService->getEstadisticasPorRango($desde, $hasta);

        return new JsonResponse([
            'periodo' => [
                'desde' => $desde->format('Y-m-d'),
                'hasta' => $hasta->format('Y-m-d'),
            ],
            'totalUsuarios' => count($estadisticas),
            'usuarios' => array_map(fn($stat) => [
                'id' => $stat['usuarioId'],
                'nombre' => $stat['nombre'],
                'apellidos' => $stat['apellidos'],
                'dni' => $stat['dni'],
                'totalPrestamos' => (int) $stat['totalPrestamos'],
            ], $estadisticas),
        ]);
    }
}
