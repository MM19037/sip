# SIP — Sistema Integral de Pedidos

Sistema de gestión de pedidos, inventario, producción y costos para empresas de personalización de artículos promocionales.

---

## Requisitos del sistema

| Requisito | Versión mínima |
|-----------|---------------|
| PHP | 8.3 |
| Composer | 2.x |
| Node.js | 18+ |
| MariaDB | 10.6+ (o MySQL 8+) |
| Git | cualquiera |

---

## Instalación paso a paso

### 1. Clonar el repositorio

```bash
git clone <url-del-repositorio> SIP
cd SIP
```

### 2. Instalar dependencias PHP

```bash
composer install
```

### 3. Configurar variables de entorno

```bash
cp .env.example .env
php artisan key:generate
```

Abrir `.env` y completar los datos de conexión a la base de datos:

```env
DB_CONNECTION=mariadb
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sistema_pedidos
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_contraseña
```

### 4. Crear e importar la base de datos

Crear la base de datos en MariaDB/MySQL:

```sql
CREATE DATABASE sistema_pedidos
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
```

Importar el esquema completo (tablas, triggers, vistas y procedimientos almacenados):

```bash
mysql -u tu_usuario -p sistema_pedidos < sistema_pedidos.sql
```

> **Alternativa con migraciones de Laravel**
> Si prefieres no usar el archivo SQL, puedes ejecutar las migraciones y el seeder:
> ```bash
> php artisan migrate
> php artisan db:seed
> ```
> Esto crea el esquema y un usuario administrador por defecto.

### 5. Instalar dependencias Node.js y compilar assets

```bash
npm install
npm run build
```

### 6. Configurar almacenamiento

```bash
php artisan storage:link
```

### 7. Iniciar el servidor de desarrollo

```bash
php artisan serve
```

Acceder a: **http://localhost:8000**

---

## Credenciales por defecto

| Campo | Valor |
|-------|-------|
| Email | `admin@sistema.local` |
| Contraseña | `Admin123!` |

> Cambiar la contraseña después del primer inicio de sesión.

---

## Levantar el entorno de desarrollo completo

Para iniciar simultáneamente el servidor PHP, la compilación de assets en modo watch y el log en tiempo real:

```bash
composer run dev
```

O de forma separada:

```bash
# Terminal 1 — servidor PHP
php artisan serve

# Terminal 2 — assets en modo watch
npm run dev

# Terminal 3 — logs en tiempo real (opcional)
php artisan pail
```

---

## Stack tecnológico

| Capa | Tecnología |
|------|-----------|
| Backend | Laravel 13.7 + PHP 8.3 |
| Frontend | Livewire 4.1 + Flux UI 2.13 |
| Estilos | TailwindCSS 4.0 + Alpine.js |
| Base de datos | MariaDB (triggers, stored procedures, vistas) |
| Build | Vite |
| Gráficas | Chart.js |
| Reportes PDF | barryvdh/laravel-dompdf |

---

## Estructura de directorios relevante

```
SIP/
├── app/
│   ├── Http/Controllers/Reportes/   # Controladores para exportar PDF/CSV
│   ├── Livewire/                    # Componentes Livewire por módulo
│   └── Models/                      # Modelos Eloquent
├── database/
│   ├── migrations/                  # Migraciones de Laravel
│   └── seeders/                     # Seeders con datos iniciales
├── resources/
│   ├── css/app.css                  # Estilos (paleta Docker Desktop + Tailwind)
│   ├── js/app.js                    # Chart.js global
│   └── views/
│       ├── livewire/                # Vistas de componentes por módulo
│       └── reportes/                # Plantillas PDF
├── routes/web.php                   # Rutas protegidas por rol
└── sistema_pedidos.sql              # Esquema completo de la base de datos
```

---

## Roles de usuario

| Rol | Acceso |
|-----|--------|
| `administrador` | Acceso total a todos los módulos |
| `recepcionista` | Pedidos, Clientes, Inventario (productos y movimientos) |
| `produccion` | Módulo de Producción |

---

## Comandos útiles

```bash
# Limpiar y recargar configuración
php artisan optimize:clear

# Recompilar assets
npm run build

# Ejecutar seeder de datos de prueba
php artisan db:seed --class=TestDataSeeder

# Ver rutas registradas
php artisan route:list

# Verificar tipos (PHPStan)
composer run types:check

# Formatear código (Laravel Pint)
composer run lint
```

---

## Notas importantes

- La base de datos utiliza **triggers de MariaDB** para gestión automática de stock, lotes FIFO y alertas. Si se migra a MySQL puro verificar compatibilidad.
- El sistema usa **costeo FIFO** — las salidas de inventario consumen los lotes más antiguos primero.
- Los reportes PDF y CSV están disponibles solo para el rol `administrador`.
- El modo oscuro/claro se guarda en `localStorage` vía el store de Flux UI.
