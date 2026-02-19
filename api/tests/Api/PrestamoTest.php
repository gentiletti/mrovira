<?php

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Libro;
use App\Entity\Prestamo;
use App\Entity\Usuario;
use Doctrine\ORM\EntityManagerInterface;

class PrestamoTest extends ApiTestCase
{
    private ?EntityManagerInterface $entityManager = null;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        // Limpiar las tablas antes de cada test
        $this->entityManager->createQuery('DELETE FROM App\Entity\Prestamo')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Libro')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Usuario')->execute();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }

    public function testGetPrestamos(): void
    {
        $client = static::createClient();

        $usuario = $this->crearUsuario('Juan', 'García López', '12345678A');
        $libro = $this->crearLibro('El Quijote', 'Miguel de Cervantes', '978-84-376-0494-7');
        $this->crearPrestamo($usuario, $libro);

        $response = $client->request('GET', '/api/prestamos');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);
        $this->assertCount(1, $data['member']);
    }

    public function testCrearPrestamo(): void
    {
        $client = static::createClient();

        $usuario = $this->crearUsuario('Juan', 'García López', '12345678A');
        $libro = $this->crearLibro('El Quijote', 'Miguel de Cervantes', '978-84-376-0494-7');

        $response = $client->request('POST', '/api/prestamos', [
            'json' => [
                'usuario' => '/api/usuarios/' . $usuario->getId(),
                'libro' => '/api/libros/' . $libro->getId(),
                'fechaPrestamo' => '2024-01-15T10:00:00+00:00',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);

        $data = $response->toArray();
        $this->assertNotNull($data['fechaPrestamo']);
        $this->assertNull($data['fechaDevolucion']);
    }

    public function testMaximoTresPrestamosActivos(): void
    {
        $client = static::createClient();

        $usuario = $this->crearUsuario('Juan', 'García López', '12345678A');

        // Crear 3 libros y 3 préstamos
        for ($i = 1; $i <= 3; $i++) {
            $libro = $this->crearLibro("Libro $i", "Autor $i", "ISBN-$i");
            $this->crearPrestamo($usuario, $libro);
        }

        // El 4to préstamo debe fallar
        $libro4 = $this->crearLibro('Libro 4', 'Autor 4', 'ISBN-4');

        $client->request('POST', '/api/prestamos', [
            'json' => [
                'usuario' => '/api/usuarios/' . $usuario->getId(),
                'libro' => '/api/libros/' . $libro4->getId(),
                'fechaPrestamo' => '2024-01-15T10:00:00+00:00',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testPermitirPrestamoTrasDevolucion(): void
    {
        $client = static::createClient();

        $usuario = $this->crearUsuario('Juan', 'García López', '12345678A');

        // Crear 3 libros y 3 préstamos
        $prestamos = [];
        for ($i = 1; $i <= 3; $i++) {
            $libro = $this->crearLibro("Libro $i", "Autor $i", "ISBN-$i");
            $prestamos[] = $this->crearPrestamo($usuario, $libro);
        }

        // Devolver uno de los libros
        $client->request('PATCH', '/api/prestamos/' . $prestamos[0]->getId(), [
            'json' => [
                'fechaDevolucion' => '2024-01-20T10:00:00+00:00',
            ],
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
            ],
        ]);

        $this->assertResponseIsSuccessful();

        // Ahora debería poder crear un nuevo préstamo
        $libro4 = $this->crearLibro('Libro 4', 'Autor 4', 'ISBN-4');

        $response = $client->request('POST', '/api/prestamos', [
            'json' => [
                'usuario' => '/api/usuarios/' . $usuario->getId(),
                'libro' => '/api/libros/' . $libro4->getId(),
                'fechaPrestamo' => '2024-01-21T10:00:00+00:00',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
    }

    public function testNoPrestarLibroYaPrestado(): void
    {
        $client = static::createClient();

        $usuario1 = $this->crearUsuario('Juan', 'García López', '12345678A');
        $usuario2 = $this->crearUsuario('María', 'Rodríguez Pérez', '87654321B');
        $libro = $this->crearLibro('El Quijote', 'Miguel de Cervantes', '978-84-376-0494-7');

        // El usuario 1 presta el libro
        $this->crearPrestamo($usuario1, $libro);

        // El usuario 2 intenta prestar el mismo libro
        $client->request('POST', '/api/prestamos', [
            'json' => [
                'usuario' => '/api/usuarios/' . $usuario2->getId(),
                'libro' => '/api/libros/' . $libro->getId(),
                'fechaPrestamo' => '2024-01-15T10:00:00+00:00',
            ],
        ]);

        $this->assertResponseStatusCodeSame(409); // Conflict
    }

    public function testDevolverLibro(): void
    {
        $client = static::createClient();

        $usuario = $this->crearUsuario('Juan', 'García López', '12345678A');
        $libro = $this->crearLibro('El Quijote', 'Miguel de Cervantes', '978-84-376-0494-7');
        $prestamo = $this->crearPrestamo($usuario, $libro);

        $response = $client->request('PATCH', '/api/prestamos/' . $prestamo->getId(), [
            'json' => [
                'fechaDevolucion' => '2024-01-20T10:00:00+00:00',
            ],
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
            ],
        ]);

        $this->assertResponseIsSuccessful();

        $data = $response->toArray();
        $this->assertNotNull($data['fechaDevolucion']);
    }

    public function testEstadisticasPorRangoFechas(): void
    {
        $client = static::createClient();

        $usuario1 = $this->crearUsuario('Juan', 'García López', '12345678A');
        $usuario2 = $this->crearUsuario('María', 'Rodríguez Pérez', '87654321B');

        // Crear préstamos para usuario1 en enero
        for ($i = 1; $i <= 3; $i++) {
            $libro = $this->crearLibro("Libro U1-$i", "Autor $i", "ISBN-U1-$i");
            $prestamo = new Prestamo();
            $prestamo->setUsuario($usuario1);
            $prestamo->setLibro($libro);
            $prestamo->setFechaPrestamo(new \DateTime("2024-01-0$i"));
            $prestamo->setFechaDevolucion(new \DateTime("2024-01-1$i"));
            $this->entityManager->persist($prestamo);
        }

        // Crear préstamos para usuario2 en enero
        for ($i = 1; $i <= 2; $i++) {
            $libro = $this->crearLibro("Libro U2-$i", "Autor $i", "ISBN-U2-$i");
            $prestamo = new Prestamo();
            $prestamo->setUsuario($usuario2);
            $prestamo->setLibro($libro);
            $prestamo->setFechaPrestamo(new \DateTime("2024-01-0$i"));
            $prestamo->setFechaDevolucion(new \DateTime("2024-01-1$i"));
            $this->entityManager->persist($prestamo);
        }

        $this->entityManager->flush();

        // Consultar estadísticas
        $response = $client->request('GET', '/api/prestamos/estadisticas?desde=2024-01-01&hasta=2024-01-31');

        $this->assertResponseIsSuccessful();

        $data = $response->toArray();
        $this->assertEquals('2024-01-01', $data['periodo']['desde']);
        $this->assertEquals('2024-01-31', $data['periodo']['hasta']);
        $this->assertEquals(2, $data['totalUsuarios']);

        // Verificar que usuario1 tiene más préstamos
        $this->assertEquals(3, $data['usuarios'][0]['totalPrestamos']);
        $this->assertEquals(2, $data['usuarios'][1]['totalPrestamos']);
    }

    public function testEstadisticasSinParametros(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/prestamos/estadisticas');

        $this->assertResponseStatusCodeSame(400);
    }

    public function testFiltrarPrestamosPorUsuario(): void
    {
        $client = static::createClient();

        $usuario1 = $this->crearUsuario('Juan', 'García López', '12345678A');
        $usuario2 = $this->crearUsuario('María', 'Rodríguez Pérez', '87654321B');

        $libro1 = $this->crearLibro('Libro 1', 'Autor 1', 'ISBN-1');
        $libro2 = $this->crearLibro('Libro 2', 'Autor 2', 'ISBN-2');

        $this->crearPrestamo($usuario1, $libro1);
        $this->crearPrestamo($usuario2, $libro2);

        $response = $client->request('GET', '/api/prestamos?usuario.id=' . $usuario1->getId());

        $this->assertResponseIsSuccessful();

        $data = $response->toArray();
        $this->assertCount(1, $data['member']);
    }

    private function crearUsuario(string $nombre, string $apellidos, string $dni): Usuario
    {
        $usuario = new Usuario();
        $usuario->setNombre($nombre);
        $usuario->setApellidos($apellidos);
        $usuario->setDni($dni);

        $this->entityManager->persist($usuario);
        $this->entityManager->flush();

        return $usuario;
    }

    private function crearLibro(string $titulo, string $autor, string $isbn): Libro
    {
        $libro = new Libro();
        $libro->setTitulo($titulo);
        $libro->setAutor($autor);
        $libro->setIsbn($isbn);

        $this->entityManager->persist($libro);
        $this->entityManager->flush();

        return $libro;
    }

    private function crearPrestamo(Usuario $usuario, Libro $libro, ?\DateTime $fechaDevolucion = null): Prestamo
    {
        $prestamo = new Prestamo();
        $prestamo->setUsuario($usuario);
        $prestamo->setLibro($libro);
        $prestamo->setFechaPrestamo(new \DateTime());
        $prestamo->setFechaDevolucion($fechaDevolucion);

        $this->entityManager->persist($prestamo);
        $this->entityManager->flush();

        return $prestamo;
    }
}
