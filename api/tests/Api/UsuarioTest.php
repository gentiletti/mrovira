<?php

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Usuario;
use Doctrine\ORM\EntityManagerInterface;

class UsuarioTest extends ApiTestCase
{
    private ?EntityManagerInterface $entityManager = null;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        // Limpiar la tabla de usuarios antes de cada test
        $this->entityManager->createQuery('DELETE FROM App\Entity\Prestamo')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Usuario')->execute();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }

    public function testGetUsuarios(): void
    {
        $client = static::createClient();

        // Crear algunos usuarios de prueba
        $this->crearUsuario('Juan', 'García López', '12345678A');
        $this->crearUsuario('María', 'Rodríguez Pérez', '87654321B');

        $response = $client->request('GET', '/api/usuarios');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);
        $this->assertCount(2, $data['member']);
    }

    public function testCrearUsuario(): void
    {
        $client = static::createClient();

        $response = $client->request('POST', '/api/usuarios', [
            'json' => [
                'nombre' => 'Pedro',
                'apellidos' => 'Martínez Silva',
                'dni' => '11111111A',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $data = $response->toArray();
        $this->assertEquals('Pedro', $data['nombre']);
        $this->assertEquals('Martínez Silva', $data['apellidos']);
        $this->assertEquals('11111111A', $data['dni']);
    }

    public function testCrearUsuarioDniDuplicado(): void
    {
        $client = static::createClient();

        // Crear un usuario primero
        $this->crearUsuario('Juan', 'García López', '12345678A');

        // Intentar crear otro con el mismo DNI
        $client->request('POST', '/api/usuarios', [
            'json' => [
                'nombre' => 'Pedro',
                'apellidos' => 'Martínez Silva',
                'dni' => '12345678A',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCrearUsuarioSinCamposRequeridos(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/usuarios', [
            'json' => [
                'nombre' => '',
                'apellidos' => '',
                'dni' => '',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testObtenerUsuario(): void
    {
        $client = static::createClient();

        $usuario = $this->crearUsuario('Juan', 'García López', '12345678A');

        $response = $client->request('GET', '/api/usuarios/' . $usuario->getId());

        $this->assertResponseIsSuccessful();

        $data = $response->toArray();
        $this->assertEquals('Juan', $data['nombre']);
        $this->assertEquals('García López', $data['apellidos']);
        $this->assertEquals('12345678A', $data['dni']);
    }

    public function testActualizarUsuario(): void
    {
        $client = static::createClient();

        $usuario = $this->crearUsuario('Juan', 'García López', '12345678A');

        $response = $client->request('PUT', '/api/usuarios/' . $usuario->getId(), [
            'json' => [
                'nombre' => 'Juan Carlos',
                'apellidos' => 'García López Actualizado',
                'dni' => '12345678A',
            ],
        ]);

        $this->assertResponseIsSuccessful();

        $data = $response->toArray();
        $this->assertEquals('Juan Carlos', $data['nombre']);
        $this->assertEquals('García López Actualizado', $data['apellidos']);
    }

    public function testEliminarUsuario(): void
    {
        $client = static::createClient();

        $usuario = $this->crearUsuario('Juan', 'García López', '12345678A');
        $usuarioId = $usuario->getId();

        $client->request('DELETE', '/api/usuarios/' . $usuarioId);

        $this->assertResponseStatusCodeSame(204);

        // Verificar que el usuario ya no existe
        $client->request('GET', '/api/usuarios/' . $usuarioId);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testBuscarUsuarioPorDni(): void
    {
        $client = static::createClient();

        $this->crearUsuario('Juan', 'García López', '12345678A');
        $this->crearUsuario('María', 'Rodríguez Pérez', '87654321B');

        $response = $client->request('GET', '/api/usuarios?dni=12345678A');

        $this->assertResponseIsSuccessful();

        $data = $response->toArray();
        $this->assertCount(1, $data['member']);
        $this->assertEquals('12345678A', $data['member'][0]['dni']);
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
}
