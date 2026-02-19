<?php

namespace App\Command;

use App\Entity\Libro;
use App\Entity\Prestamo;
use App\Entity\Usuario;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed-data',
    description: 'Carga datos de prueba en la base de datos',
)]
class SeedDataCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Cargando datos de prueba');

        // Limpiar datos existentes
        $io->section('Limpiando datos existentes...');
        $this->entityManager->createQuery('DELETE FROM App\Entity\Prestamo')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Libro')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Usuario')->execute();

        // Crear usuarios
        $io->section('Creando usuarios...');
        $usuarios = $this->crearUsuarios();
        $io->success(sprintf('Creados %d usuarios', count($usuarios)));

        // Crear libros
        $io->section('Creando libros...');
        $libros = $this->crearLibros();
        $io->success(sprintf('Creados %d libros', count($libros)));

        // Crear prestamos
        $io->section('Creando prestamos...');
        $prestamos = $this->crearPrestamos($usuarios, $libros);
        $io->success(sprintf('Creados %d prestamos', count($prestamos)));

        $this->entityManager->flush();

        $io->success('Datos de prueba cargados correctamente');

        return Command::SUCCESS;
    }

    private function crearUsuarios(): array
    {
        $datosUsuarios = [
            ['nombre' => 'Juan', 'apellidos' => 'Garcia Lopez', 'dni' => '12345678A'],
            ['nombre' => 'Maria', 'apellidos' => 'Rodriguez Martinez', 'dni' => '23456789B'],
            ['nombre' => 'Carlos', 'apellidos' => 'Fernandez Ruiz', 'dni' => '34567890C'],
            ['nombre' => 'Ana', 'apellidos' => 'Martinez Sanchez', 'dni' => '45678901D'],
            ['nombre' => 'Pedro', 'apellidos' => 'Lopez Garcia', 'dni' => '56789012E'],
            ['nombre' => 'Laura', 'apellidos' => 'Sanchez Perez', 'dni' => '67890123F'],
            ['nombre' => 'Miguel', 'apellidos' => 'Perez Gonzalez', 'dni' => '78901234G'],
            ['nombre' => 'Sofia', 'apellidos' => 'Gonzalez Diaz', 'dni' => '89012345H'],
            ['nombre' => 'David', 'apellidos' => 'Diaz Hernandez', 'dni' => '90123456I'],
            ['nombre' => 'Carmen', 'apellidos' => 'Hernandez Moreno', 'dni' => '01234567J'],
        ];

        $usuarios = [];
        foreach ($datosUsuarios as $datos) {
            $usuario = new Usuario();
            $usuario->setNombre($datos['nombre']);
            $usuario->setApellidos($datos['apellidos']);
            $usuario->setDni($datos['dni']);
            $this->entityManager->persist($usuario);
            $usuarios[] = $usuario;
        }

        return $usuarios;
    }

    private function crearLibros(): array
    {
        $datosLibros = [
            ['titulo' => 'Don Quijote de la Mancha', 'autor' => 'Miguel de Cervantes', 'isbn' => '978-84-376-0494-7'],
            ['titulo' => 'Cien anos de soledad', 'autor' => 'Gabriel Garcia Marquez', 'isbn' => '978-84-376-0495-4'],
            ['titulo' => '1984', 'autor' => 'George Orwell', 'isbn' => '978-84-376-0496-1'],
            ['titulo' => 'El principito', 'autor' => 'Antoine de Saint-Exupery', 'isbn' => '978-84-376-0497-8'],
            ['titulo' => 'Crimen y castigo', 'autor' => 'Fiodor Dostoievski', 'isbn' => '978-84-376-0498-5'],
            ['titulo' => 'Orgullo y prejuicio', 'autor' => 'Jane Austen', 'isbn' => '978-84-376-0499-2'],
            ['titulo' => 'El senor de los anillos', 'autor' => 'J.R.R. Tolkien', 'isbn' => '978-84-376-0500-5'],
            ['titulo' => 'Harry Potter y la piedra filosofal', 'autor' => 'J.K. Rowling', 'isbn' => '978-84-376-0501-2'],
            ['titulo' => 'El codigo Da Vinci', 'autor' => 'Dan Brown', 'isbn' => '978-84-376-0502-9'],
            ['titulo' => 'La sombra del viento', 'autor' => 'Carlos Ruiz Zafon', 'isbn' => '978-84-376-0503-6'],
            ['titulo' => 'Los pilares de la Tierra', 'autor' => 'Ken Follett', 'isbn' => '978-84-376-0504-3'],
            ['titulo' => 'El alquimista', 'autor' => 'Paulo Coelho', 'isbn' => '978-84-376-0505-0'],
            ['titulo' => 'Rayuela', 'autor' => 'Julio Cortazar', 'isbn' => '978-84-376-0506-7'],
            ['titulo' => 'La casa de los espiritus', 'autor' => 'Isabel Allende', 'isbn' => '978-84-376-0507-4'],
            ['titulo' => 'El amor en los tiempos del colera', 'autor' => 'Gabriel Garcia Marquez', 'isbn' => '978-84-376-0508-1'],
            ['titulo' => 'Fahrenheit 451', 'autor' => 'Ray Bradbury', 'isbn' => '978-84-376-0509-8'],
            ['titulo' => 'Matar a un ruisenor', 'autor' => 'Harper Lee', 'isbn' => '978-84-376-0510-4'],
            ['titulo' => 'El gran Gatsby', 'autor' => 'F. Scott Fitzgerald', 'isbn' => '978-84-376-0511-1'],
            ['titulo' => 'Cumbres borrascosas', 'autor' => 'Emily Bronte', 'isbn' => '978-84-376-0512-8'],
            ['titulo' => 'La metamorfosis', 'autor' => 'Franz Kafka', 'isbn' => '978-84-376-0513-5'],
        ];

        $libros = [];
        foreach ($datosLibros as $datos) {
            $libro = new Libro();
            $libro->setTitulo($datos['titulo']);
            $libro->setAutor($datos['autor']);
            $libro->setIsbn($datos['isbn']);
            $this->entityManager->persist($libro);
            $libros[] = $libro;
        }

        return $libros;
    }

    private function crearPrestamos(array $usuarios, array $libros): array
    {
        $prestamos = [];
        $libroIndex = 0;

        // Usuario 1: 3 prestamos activos (maximo)
        for ($i = 0; $i < 3; $i++) {
            $prestamo = new Prestamo();
            $prestamo->setUsuario($usuarios[0]);
            $prestamo->setLibro($libros[$libroIndex++]);
            $prestamo->setFechaPrestamo(new \DateTime('-' . (10 - $i) . ' days'));
            $this->entityManager->persist($prestamo);
            $prestamos[] = $prestamo;
        }

        // Usuario 2: 2 prestamos activos
        for ($i = 0; $i < 2; $i++) {
            $prestamo = new Prestamo();
            $prestamo->setUsuario($usuarios[1]);
            $prestamo->setLibro($libros[$libroIndex++]);
            $prestamo->setFechaPrestamo(new \DateTime('-' . (8 - $i) . ' days'));
            $this->entityManager->persist($prestamo);
            $prestamos[] = $prestamo;
        }

        // Usuario 3: 1 prestamo activo + 2 devueltos
        $prestamo = new Prestamo();
        $prestamo->setUsuario($usuarios[2]);
        $prestamo->setLibro($libros[$libroIndex++]);
        $prestamo->setFechaPrestamo(new \DateTime('-5 days'));
        $this->entityManager->persist($prestamo);
        $prestamos[] = $prestamo;

        for ($i = 0; $i < 2; $i++) {
            $prestamo = new Prestamo();
            $prestamo->setUsuario($usuarios[2]);
            $prestamo->setLibro($libros[$libroIndex++]);
            $prestamo->setFechaPrestamo(new \DateTime('-30 days'));
            $prestamo->setFechaDevolucion(new \DateTime('-20 days'));
            $this->entityManager->persist($prestamo);
            $prestamos[] = $prestamo;
        }

        // Usuario 4: Solo prestamos devueltos (historico)
        for ($i = 0; $i < 3; $i++) {
            $prestamo = new Prestamo();
            $prestamo->setUsuario($usuarios[3]);
            $prestamo->setLibro($libros[$libroIndex++]);
            $prestamo->setFechaPrestamo(new \DateTime('-60 days'));
            $prestamo->setFechaDevolucion(new \DateTime('-45 days'));
            $this->entityManager->persist($prestamo);
            $prestamos[] = $prestamo;
        }

        // Usuario 5: 1 prestamo activo
        $prestamo = new Prestamo();
        $prestamo->setUsuario($usuarios[4]);
        $prestamo->setLibro($libros[$libroIndex++]);
        $prestamo->setFechaPrestamo(new \DateTime('-3 days'));
        $this->entityManager->persist($prestamo);
        $prestamos[] = $prestamo;

        // Usuarios 6-10: Sin prestamos (nuevos usuarios)

        return $prestamos;
    }
}
