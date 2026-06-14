@extends('docs.base')

@section('contenido')
<div class="cover">
    <div class="sistema">SIP — Sistema Integral de Pedidos</div>
    <h1>Manual de Usuario</h1>
    <div class="subtitulo">Guía de uso de módulos del sistema</div>
    <div class="meta">Versión 1.0 &nbsp;|&nbsp; {{ now()->format('d/m/Y') }}</div>
</div>

<h2>Acceso al sistema</h2>
<p>Ingresar a la URL del sistema en el navegador. Escribir el correo electrónico y la contraseña asignados por el administrador.</p>
<table>
    <thead><tr><th>Rol</th><th>Módulos disponibles</th></tr></thead>
    <tbody>
        <tr><td>Administrador</td><td>Todos los módulos</td></tr>
        <tr><td>Recepcionista</td><td>Dashboard, Pedidos, Clientes, Inventario (productos y movimientos)</td></tr>
        <tr><td>Producción</td><td>Dashboard, Órdenes de producción</td></tr>
    </tbody>
</table>

<h2>Dashboard</h2>
<p>La pantalla principal muestra un resumen en tiempo real. Al hacer clic en cualquier tarjeta el sistema navega al módulo relacionado con el filtro aplicado automáticamente.</p>
<table>
    <thead><tr><th>Tarjeta</th><th>Módulo destino</th><th>Filtro</th></tr></thead>
    <tbody>
        <tr><td>Pedidos activos</td><td>Pedidos</td><td>Sin filtro</td></tr>
        <tr><td>Esperando stock</td><td>Pedidos</td><td>Estado: Esperando stock</td></tr>
        <tr><td>Pendientes</td><td>Pedidos</td><td>Estado: Pendiente</td></tr>
        <tr><td>En producción</td><td>Producción</td><td>Estado: En proceso</td></tr>
        <tr><td>Listos para entrega</td><td>Pedidos</td><td>Estado: Listo</td></tr>
        <tr><td>Entregados hoy</td><td>Pedidos</td><td>Estado: Entregado</td></tr>
        <tr><td>Ventas / Ganancia del mes</td><td>Rentabilidad</td><td>—</td></tr>
        <tr><td>Alertas de stock</td><td>Solicitudes</td><td>—</td></tr>
    </tbody>
</table>
<h3>Gráficas del dashboard</h3>
<ul>
    <li><strong>Pedidos por estado</strong> — distribución de pedidos activos (donut).</li>
    <li><strong>Ventas del mes</strong> — total de ventas por día del mes en curso (barras).</li>
    <li><strong>Movimientos de inventario</strong> — entradas y salidas del mes (línea).</li>
    <li><strong>Stock por categoría</strong> — stock actual vs. mínimo por categoría (barras agrupadas).</li>
</ul>

<h2>Módulo de Pedidos</h2>
<h3>Estados de un pedido</h3>
<table>
    <thead><tr><th>Estado</th><th>Significado</th></tr></thead>
    <tbody>
        <tr><td>Esperando stock</td><td>Bloqueado por falta de inventario. Se genera solicitud automática.</td></tr>
        <tr><td>Pendiente</td><td>Confirmado, stock reservado, listo para ir a producción.</td></tr>
        <tr><td>En producción</td><td>Tiene una orden de producción activa.</td></tr>
        <tr><td>Listo</td><td>Producción completada, pendiente de entrega al cliente.</td></tr>
        <tr><td>Entregado</td><td>Finalizado y entregado.</td></tr>
        <tr><td>Cancelado</td><td>Cancelado; el stock reservado es liberado automáticamente.</td></tr>
    </tbody>
</table>
<h3>Crear un pedido</h3>
<ol>
    <li>Clic en <strong>Nuevo pedido</strong>.</li>
    <li>Seleccionar el cliente.</li>
    <li>Agregar líneas de productos con cantidad y precio.</li>
    <li>El sistema verifica el stock disponible al guardar:
        <ul>
            <li>Si hay stock: queda en <strong>Pendiente</strong> y reserva el inventario (FIFO).</li>
            <li>Si no hay stock: queda en <strong>Esperando stock</strong> y genera una solicitud de reabastecimiento.</li>
        </ul>
    </li>
</ol>

<h2>Módulo de Clientes</h2>
<p>Directorio de clientes. Permite buscar, crear, editar y desactivar clientes. Desde el perfil de un cliente se puede ver su historial de pedidos.</p>

<h2>Módulo de Inventario</h2>

<h3>Productos</h3>
<p>Catálogo con información de costos y niveles de stock.</p>
<table>
    <thead><tr><th>Columna</th><th>Descripción</th></tr></thead>
    <tbody>
        <tr><td>Stock actual</td><td>Unidades físicas en almacén</td></tr>
        <tr><td>Reservado</td><td>Unidades comprometidas por pedidos activos</td></tr>
        <tr><td>Disponible</td><td>Stock actual menos reservado</td></tr>
        <tr><td>Mínimo</td><td>Umbral; al caer por debajo se genera una alerta</td></tr>
    </tbody>
</table>
<p>El botón <strong>Exportar</strong> permite descargar el listado en PDF o CSV.</p>

<h3>Movimientos de inventario</h3>
<table>
    <thead><tr><th>Tipo</th><th>Cuándo se usa</th></tr></thead>
    <tbody>
        <tr><td>Entrada</td><td>Recepción de mercancía. Crea automáticamente un lote FIFO.</td></tr>
        <tr><td>Salida</td><td>Reducción manual de stock.</td></tr>
        <tr><td>Ajuste</td><td>Corrección por conteo físico.</td></tr>
    </tbody>
</table>
<div class="note">La parte superior muestra las alertas de stock bajo activas con los productos que requieren reabastecimiento.</div>

<h3>Solicitudes de reabastecimiento</h3>
<p>Se generan automáticamente cuando:</p>
<ul>
    <li>El stock disponible cae por debajo del mínimo configurado.</li>
    <li>Un pedido no puede reservar stock por falta de inventario.</li>
</ul>
<p>Se pueden filtrar por tipo (General / Vinculada a pedido) y gestionar su estado.</p>

<h2>Módulo de Producción</h2>

<h3>Panel de operarios</h3>
<p>Tarjetas por operario con estado actual, orden asignada, tiempo transcurrido y cola de órdenes.</p>

<h3>Flujo de una orden de producción</h3>
<ol>
    <li>La orden se crea automáticamente al confirmar un pedido.</li>
    <li><strong>Asignar operario</strong> — el sistema avisa si el operario ya tiene trabajo activo (no bloquea la asignación).</li>
    <li><strong>Iniciar</strong> — registra la hora de inicio. Requiere operario asignado. Bloqueado si el operario tiene otra orden en proceso.</li>
    <li><strong>Pausar / Reanudar</strong> — disponible durante el proceso.</li>
    <li><strong>Completar</strong> — el pedido pasa a <strong>Listo para entrega</strong> automáticamente.</li>
</ol>

<h3>Semáforo de tiempo</h3>
<table>
    <thead><tr><th>Color</th><th>Tiempo transcurrido</th></tr></thead>
    <tbody>
        <tr><td>Verde</td><td>Menos de 2 horas</td></tr>
        <tr><td>Amarillo</td><td>Entre 2 y 4 horas</td></tr>
        <tr><td>Rojo</td><td>Más de 4 horas</td></tr>
    </tbody>
</table>

<h2>Módulo de Costos y Precios</h2>

<h3>Valoración de inventario</h3>
<p>Valor del inventario calculado con el método <strong>FIFO</strong> (First In, First Out). Muestra resumen global por categoría y detalle por producto con costo promedio FIFO, valor total y lotes activos.</p>

<h3>Lotes</h3>
<p>Trazabilidad completa de cada entrada de stock como lote independiente. Permite identificar el origen y costo de cada unidad en almacén.</p>
<table>
    <thead><tr><th>Columna</th><th>Descripción</th></tr></thead>
    <tbody>
        <tr><td>Disponible</td><td>Unidades del lote en almacén</td></tr>
        <tr><td>Reservado</td><td>Comprometidas a pedidos activos</td></tr>
        <tr><td>Libre</td><td>Disponible menos reservado</td></tr>
        <tr><td>Valor disponible</td><td>Disponible × costo unitario del lote</td></tr>
    </tbody>
</table>

<h3>Rentabilidad</h3>
<p>Análisis de márgenes con costos FIFO reales por producto y período (año / mes). El margen % se colorea: verde ≥ 20 %, amarillo ≥ 10 %, rojo &lt; 10 %.</p>

<h2>Exportación de reportes</h2>
<table>
    <thead><tr><th>Reporte</th><th>Módulo</th><th>Formato</th></tr></thead>
    <tbody>
        <tr><td>Productos</td><td>Inventario → Productos</td><td>PDF / CSV</td></tr>
        <tr><td>Movimientos</td><td>Inventario → Movimientos</td><td>PDF / CSV</td></tr>
        <tr><td>Valoración FIFO</td><td>Costos → Valoración</td><td>PDF / CSV</td></tr>
        <tr><td>Lotes activos</td><td>Costos → Lotes</td><td>PDF / CSV</td></tr>
        <tr><td>Rentabilidad</td><td>Costos → Rentabilidad</td><td>PDF / CSV</td></tr>
    </tbody>
</table>

<h2>Administración de usuarios</h2>
<p>Accesible solo para el administrador. Permite crear, editar y desactivar usuarios del sistema asignándoles nombre, correo, contraseña y rol.</p>

<h2>Modo claro / oscuro</h2>
<p>El botón <strong>Modo claro / Modo oscuro</strong> está en la parte inferior del menú lateral. La preferencia se guarda automáticamente para futuras sesiones.</p>
@endsection
