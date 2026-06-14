@extends('docs.base')

@section('contenido')
<div class="cover">
    <div class="sistema">SIP — Sistema Integral de Pedidos</div>
    <h1>Guía de Instalación</h1>
    <div class="subtitulo">Manual técnico para desarrolladores</div>
    <div class="meta">Versión 1.0 &nbsp;|&nbsp; {{ now()->format('d/m/Y') }}</div>
</div>

<h2>1. Requisitos del sistema</h2>
<table>
    <thead><tr><th>Componente</th><th>Versión mínima</th><th>Notas</th></tr></thead>
    <tbody>
        <tr><td>PHP</td><td>8.3</td><td>Extensiones: pdo_mysql, mbstring, xml, zip, bcmath, gd</td></tr>
        <tr><td>Composer</td><td>2.x</td><td>Gestor de dependencias PHP</td></tr>
        <tr><td>Node.js</td><td>18 LTS</td><td>Incluye npm para compilar assets</td></tr>
        <tr><td>MariaDB</td><td>10.6+</td><td>Compatible con MySQL 8+</td></tr>
        <tr><td>Git</td><td>cualquiera</td><td>Para clonar el repositorio</td></tr>
    </tbody>
</table>

<h2>2. Clonar el repositorio</h2>
<pre>git clone &lt;url-del-repositorio&gt; SIP
cd SIP</pre>

<h2>3. Dependencias PHP</h2>
<pre>composer install</pre>

<h2>4. Variables de entorno</h2>
<p>Copiar el archivo de ejemplo y generar la clave de la aplicación:</p>
<pre>cp .env.example .env
php artisan key:generate</pre>
<p>Abrir <code>.env</code> y configurar la conexión a la base de datos:</p>
<pre>DB_CONNECTION=mariadb
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sistema_pedidos
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_contraseña</pre>

<h2>5. Base de datos</h2>
<p>Crear la base de datos en MariaDB/MySQL:</p>
<pre>CREATE DATABASE sistema_pedidos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;</pre>

<h3>Opción A — Importar script SQL (recomendado)</h3>
<p>El archivo <code>sistema_pedidos.sql</code> en la raíz del proyecto contiene el esquema completo con tablas, triggers, vistas y procedimientos almacenados:</p>
<pre>mysql -u tu_usuario -p sistema_pedidos &lt; sistema_pedidos.sql</pre>

<h3>Opción B — Migraciones de Laravel</h3>
<p>Si prefieres usar el sistema de migraciones de Laravel:</p>
<pre>php artisan migrate
php artisan db:seed</pre>
<div class="note">El seeder crea el usuario administrador por defecto y categorías y productos de ejemplo.</div>

<h2>6. Dependencias frontend y compilación de assets</h2>
<pre>npm install
npm run build</pre>

<h2>7. Enlace de almacenamiento</h2>
<pre>php artisan storage:link</pre>

<h2>8. Iniciar el servidor de desarrollo</h2>
<pre>php artisan serve</pre>
<p>Abrir en el navegador: <code>http://localhost:8000</code></p>

<hr>

<h2>Credenciales por defecto</h2>
<table>
    <thead><tr><th>Campo</th><th>Valor</th></tr></thead>
    <tbody>
        <tr><td>Email</td><td>admin@sistema.local</td></tr>
        <tr><td>Contraseña</td><td>Admin123!</td></tr>
        <tr><td>Rol</td><td>Administrador</td></tr>
    </tbody>
</table>
<div class="warn">Cambiar la contraseña después del primer inicio de sesión en producción.</div>

<h2>Entorno de desarrollo completo</h2>
<p>Para iniciar simultáneamente el servidor, compilación en modo watch y logs:</p>
<pre>composer run dev</pre>
<p>O por separado en terminales distintas:</p>
<pre># Terminal 1
php artisan serve

# Terminal 2
npm run dev

# Terminal 3 (logs en tiempo real)
php artisan pail</pre>

<h2>Stack tecnológico</h2>
<table>
    <thead><tr><th>Capa</th><th>Tecnología</th></tr></thead>
    <tbody>
        <tr><td>Backend</td><td>Laravel 13.7 + PHP 8.3</td></tr>
        <tr><td>Frontend reactivo</td><td>Livewire 4.1 + Flux UI 2.13</td></tr>
        <tr><td>Estilos</td><td>TailwindCSS 4.0 + Alpine.js</td></tr>
        <tr><td>Base de datos</td><td>MariaDB con triggers, stored procedures y vistas</td></tr>
        <tr><td>Build</td><td>Vite</td></tr>
        <tr><td>Gráficas</td><td>Chart.js</td></tr>
        <tr><td>Reportes PDF</td><td>barryvdh/laravel-dompdf</td></tr>
    </tbody>
</table>

<h2>Comandos útiles</h2>
<table>
    <thead><tr><th>Comando</th><th>Descripción</th></tr></thead>
    <tbody>
        <tr><td><code>php artisan optimize:clear</code></td><td>Limpiar cachés de configuración, rutas y vistas</td></tr>
        <tr><td><code>npm run build</code></td><td>Recompilar assets para producción</td></tr>
        <tr><td><code>php artisan db:seed --class=TestDataSeeder</code></td><td>Cargar datos de prueba adicionales</td></tr>
        <tr><td><code>php artisan route:list</code></td><td>Ver todas las rutas registradas</td></tr>
        <tr><td><code>composer run lint</code></td><td>Formatear código con Laravel Pint</td></tr>
        <tr><td><code>composer run types:check</code></td><td>Verificar tipos con PHPStan</td></tr>
    </tbody>
</table>

<h2>Notas importantes</h2>
<ul>
    <li>La base de datos usa <strong>triggers de MariaDB</strong> para gestión automática de stock, lotes FIFO y alertas. Verificar compatibilidad si se migra a MySQL puro.</li>
    <li>El sistema usa <strong>costeo FIFO</strong>: las salidas de inventario consumen los lotes más antiguos primero.</li>
    <li>Los reportes PDF y CSV están disponibles solo para el rol <code>administrador</code>.</li>
    <li>El modo oscuro/claro se guarda en <code>localStorage</code> del navegador vía Flux UI.</li>
</ul>
@endsection
