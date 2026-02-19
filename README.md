# Sistema de Gestión de Biblioteca

API REST + Frontend para gestionar usuarios, libros y préstamos de una biblioteca.

## Stack Tecnológico

- **Backend**: Symfony 7.x + API-Platform 4.x + PHP 8.4
- **Frontend**: Vue.js 3 + TypeScript + Vite
- **Base de datos**: PostgreSQL 16
- **Contenedores**: Docker + Docker Compose

## Requisitos Previos

- Docker y Docker Compose instalados
- Git

> **Windows**: se recomienda correr todos los comandos desde **WSL** (Windows Subsystem for Linux).

## Instalación y Ejecución

### 1. Clonar el repositorio

```bash
git clone git@github.com:gentiletti/mrovira.git
cd mrovira
```

### 2. Configurar variables de entorno

```bash
cp api/.env.example api/.env
```

> El archivo `api/.env.example` contiene los valores por defecto listos para usar con Docker. No es necesario modificarlos para correr el proyecto localmente.

### 3. Levantar los contenedores

```bash
docker compose up -d --build
```

### 4. Instalar dependencias del backend

```bash
docker compose exec php composer install
```

### 5. Ejecutar migraciones de base de datos

```bash
docker compose exec php bin/console doctrine:migrations:migrate --no-interaction
```

> **Troubleshooting — "permission denied" en `bin/console`:**
> Si aparece `permission denied`, el archivo no tiene el bit ejecutable. Corregilo con:
> ```bash
> chmod +x api/bin/console api/bin/phpunit
> ```
> Y luego volvé a correr el comando de migraciones.

### 6. Acceder a la aplicación

- **API**: http://localhost:8080/api
- **Documentación API (Swagger)**: http://localhost:8080/api/docs
- **Frontend**: http://localhost:3000

---

## Estructura del Proyecto

```
mrovira/
├── api/                      # Backend Symfony
│   ├── src/
│   │   ├── Entity/          # Entidades Doctrine
│   │   ├── Repository/      # Repositorios
│   │   ├── Service/         # Servicios (lógica de negocio)
│   │   ├── State/           # State Processors API-Platform
│   │   └── Controller/      # Controladores
│   └── tests/               # Tests automatizados
├── frontend/                 # Frontend Vue.js
│   └── src/
│       ├── components/      # Componentes Vue
│       ├── views/           # Vistas
│       ├── services/        # Servicios API
│       └── types/           # Tipos TypeScript
├── docker/                   # Configuración Docker
└── docker-compose.yml
```

---

## Endpoints de la API

### Usuarios
| Método | Endpoint              | Descripción         |
|--------|-----------------------|---------------------|
| GET    | /api/usuarios         | Listar usuarios     |
| POST   | /api/usuarios         | Crear usuario       |
| GET    | /api/usuarios/{id}    | Obtener usuario     |
| PUT    | /api/usuarios/{id}    | Actualizar usuario  |
| DELETE | /api/usuarios/{id}    | Eliminar usuario    |

### Libros
| Método | Endpoint            | Descripción       |
|--------|---------------------|-------------------|
| GET    | /api/libros         | Listar libros     |
| POST   | /api/libros         | Crear libro       |
| GET    | /api/libros/{id}    | Obtener libro     |
| PUT    | /api/libros/{id}    | Actualizar libro  |
| DELETE | /api/libros/{id}    | Eliminar libro    |

### Préstamos
| Método | Endpoint                    | Descripción                        |
|--------|-----------------------------|------------------------------------|
| GET    | /api/prestamos              | Listar préstamos                   |
| POST   | /api/prestamos              | Crear préstamo (máx 3 activos)     |
| GET    | /api/prestamos/{id}         | Obtener préstamo                   |
| PATCH  | /api/prestamos/{id}         | Devolver libro                     |
| DELETE | /api/prestamos/{id}         | Eliminar préstamo                  |

### Estadísticas
| Método | Endpoint                                        | Descripción                    |
|--------|-------------------------------------------------|--------------------------------|
| GET    | /api/prestamos/estadisticas?desde=X&hasta=Y     | Préstamos por usuario en rango |

---

## Validaciones de Negocio

### Límite de 3 préstamos activos por usuario
El sistema valida que cada usuario no pueda tener más de 3 préstamos activos simultáneamente. Un préstamo se considera "activo" cuando no tiene fecha de devolución.

Si un usuario intenta realizar un 4to préstamo, la API responderá con un error HTTP 422 (Unprocessable Entity).

### Disponibilidad de libros
Un libro no puede ser prestado si ya está actualmente prestado a otro usuario. La API responderá con un error HTTP 409 (Conflict).

---

## Patrón de Diseño: Service Layer

Se ha implementado el patrón **Service Layer** en la clase `PrestamoService` para encapsular la lógica de negocio.

### Justificación

1. **Separación de responsabilidades (SRP)**: La lógica de negocio está separada de los controladores y entidades.
2. **Testabilidad**: El servicio puede ser testeado de forma aislada mediante unit tests.
3. **Reutilización**: La lógica puede ser usada desde múltiples puntos de entrada (API, CLI, etc.).
4. **Mantenibilidad**: Los cambios en las reglas de negocio se realizan en un solo lugar.

### Implementación

```php
class PrestamoService
{
    public function crearPrestamo(Prestamo $prestamo): Prestamo
    {
        // Validar límite de préstamos
        $this->validarLimitePrestamos($usuario);

        // Validar disponibilidad del libro
        $this->validarDisponibilidadLibro($libro);

        // Persistir y retornar
        ...
    }
}
```

---

## Ejecutar Tests

```bash
# Ejecutar todos los tests
docker compose exec php bin/phpunit

# Ejecutar tests con cobertura
docker compose exec php bin/phpunit --coverage-html coverage
```

---

## Comandos Útiles

```bash
# Ver logs de los contenedores
docker compose logs -f

# Acceder al contenedor PHP
docker compose exec php sh

# Limpiar caché de Symfony
docker compose exec php bin/console cache:clear

# Crear una nueva migración
docker compose exec php bin/console make:migration

# Ver rutas disponibles
docker compose exec php bin/console debug:router
```

---

## Autor

Desarrollado como prueba técnica.
