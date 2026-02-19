<?php

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Libro;
use Doctrine\ORM\EntityManagerInterface;

class LibroTest extends ApiTestCase
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
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }

    public function testGetLibros(): void
    {
        $client = static::createClient();

        // Crear algunos libros de prueba
        $this->crearLibro('El Quijote', 'Miguel de Cervantes', '978-84-376-0494-7');
        $this->crearLibro('Cien años de soledad', 'Gabriel García Márquez', '978-84-376-0495-4');

        $response = $client->request('GET', '/api/libros');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);
        $this->assertCount(2, $data['member']);
    }

    public function testCrearLibro(): void
    {
        $client = static::createClient();

        $response = $client->request('POST', '/api/libros', [
            'json' => [
                'titulo' => '1984',
                'autor' => 'George Orwell',
                'isbn' => '978-84-376-0496-1',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $data = $response->toArray();
        $this->assertEquals('1984', $data['titulo']);
        $this->assertEquals('George Orwell', $data['autor']);
        $this->assertEquals('978-84-376-0496-1', $data['isbn']);
    }

    public function testCrearLibroIsbnDuplicado(): void
    {
        $client = static::createClient();

        // Crear un libro primero
        $this->crearLibro('El Quijote', 'Miguel de Cervantes', '978-84-376-0494-7');

        // Intentar crear otro con el mismo ISBN
        $client->request('POST', '/api/libros', [
            'json' => [
                'titulo' => 'Otro libro',
                'autor' => 'Otro autor',
                'isbn' => '978-84-376-0494-7',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCrearLibroSinCamposRequeridos(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/libros', [
            'json' => [
                'titulo' => '',
                'autor' => '',
                'isbn' => '',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testObtenerLibro(): void
    {
        $client = static::createClient();

        $libro = $this->crearLibro('El Quijote', 'Miguel de Cervantes', '978-84-376-0494-7');

        $response = $client->request('GET', '/api/libros/' . $libro->getId());

        $this->assertResponseIsSuccessful();

        $data = $response->toArray();
        $this->assertEquals('El Quijote', $data['titulo']);
        $this->assertEquals('Miguel de Cervantes', $data['autor']);
        $this->assertEquals('978-84-376-0494-7', $data['isbn']);
    }

    public function testActualizarLibro(): void
    {
        $client = static::createClient();

        $libro = $this->crearLibro('El Quijote', 'Miguel de Cervantes', '978-84-376-0494-7');

        $response = $client->request('PUT', '/api/libros/' . $libro->getId(), [
            'json' => [
                'titulo' => 'Don Quijote de la Mancha',
                'autor' => 'Miguel de Cervantes Saavedra',
                'isbn' => '978-84-376-0494-7',
            ],
        ]);

        $this->assertResponseIsSuccessful();

        $data = $response->toArray();
        $this->assertEquals('Don Quijote de la Mancha', $data['titulo']);
        $this->assertEquals('Miguel de Cervantes Saavedra', $data['autor']);
    }

    public function testEliminarLibro(): void
    {
        $client = static::createClient();

        $libro = $this->crearLibro('El Quijote', 'Miguel de Cervantes', '978-84-376-0494-7');
        $libroId = $libro->getId();

        $client->request('DELETE', '/api/libros/' . $libroId);

        $this->assertResponseStatusCodeSame(204);

        // Verificar que el libro ya no existe
        $client->request('GET', '/api/libros/' . $libroId);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testBuscarLibroPorIsbn(): void
    {
        $client = static::createClient();

        $this->crearLibro('El Quijote', 'Miguel de Cervantes', '978-84-376-0494-7');
        $this->crearLibro('Cien años de soledad', 'Gabriel García Márquez', '978-84-376-0495-4');

        $response = $client->request('GET', '/api/libros?isbn=978-84-376-0494-7');

        $this->assertResponseIsSuccessful();

        $data = $response->toArray();
        $this->assertCount(1, $data['member']);
        $this->assertEquals('978-84-376-0494-7', $data['member'][0]['isbn']);
    }

    public function testBuscarLibroPorTitulo(): void
    {
        $client = static::createClient();

        $this->crearLibro('El Quijote', 'Miguel de Cervantes', '978-84-376-0494-7');
        $this->crearLibro('Cien años de soledad', 'Gabriel García Márquez', '978-84-376-0495-4');

        $response = $client->request('GET', '/api/libros?titulo=Quijote');

        $this->assertResponseIsSuccessful();

        $data = $response->toArray();
        $this->assertCount(1, $data['member']);
        $this->assertStringContainsString('Quijote', $data['member'][0]['titulo']);
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
}
