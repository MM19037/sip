# Manual de Usuario — SIP
## Sistema Integral de Pedidos

---

## Acceso al sistema

Ingresar a la URL del sistema en el navegador. En la pantalla de inicio de sesión escribir el correo electrónico y la contraseña asignados por el administrador.

El sistema tiene tres niveles de acceso:

| Rol | Módulos disponibles |
|-----|-------------------|
| Administrador | Todos los módulos |
| Recepcionista | Dashboard, Pedidos, Clientes, Inventario (productos y movimientos) |
| Producción | Dashboard, Órdenes de producción |

---

## Dashboard

La pantalla principal muestra un resumen en tiempo real del estado del negocio.

### Tarjetas de resumen
Nueve tarjetas muestran los indicadores clave. Al hacer clic en cualquier tarjeta el sistema navega directamente al módulo relacionado con el filtro aplicado:

| Tarjeta | Módulo destino | Filtro |
|---------|---------------|--------|
| Pedidos activos | Pedidos | Sin filtro |
| Esperando stock | Pedidos | Estado: Esperando stock |
| Pendientes | Pedidos | Estado: Pendiente |
| En producción | Producción | Estado: En proceso |
| Listos para entrega | Pedidos | Estado: Listo |
| Entregados hoy | Pedidos | Estado: Entregado |
| Ventas del mes | Rentabilidad | — |
| Ganancia del mes | Rentabilidad | — |
| Alertas de stock | Solicitudes de reabastecimiento | — |

### Gráficas
- **Pedidos por estado** — distribución de pedidos activos por estado (donut).
- **Ventas del mes** — total de ventas por día del mes en curso (barras).
- **Movimientos de inventario** — entradas y salidas de stock del mes (línea).
- **Stock por categoría** — comparación entre stock actual y stock mínimo por categoría (barras agrupadas).

---

## Módulo de Pedidos

Acceso: menú lateral → **Recepción → Pedidos**

### Listado de pedidos
Muestra todos los pedidos con filtros por búsqueda (ID o cliente) y estado. Los estados posibles son:

| Estado | Significado |
|--------|-------------|
| Esperando stock | El pedido está bloqueado porque no hay suficiente inventario |
| Pendiente | Pedido confirmado, listo para ir a producción |
| En producción | Tiene una orden de producción activa |
| Listo | Producción completada, pendiente de entrega |
| Entregado | Pedido finalizado y entregado al cliente |
| Cancelado | Pedido cancelado |

### Crear pedido
1. Hacer clic en **Nuevo pedido**.
2. Seleccionar el cliente (o crear uno nuevo).
3. Agregar líneas de productos indicando cantidad y precio.
4. El sistema verifica el stock disponible automáticamente.
   - Si hay stock suficiente: el pedido queda en estado **Pendiente** y reserva el inventario.
   - Si no hay stock: el pedido queda en **Esperando stock** y se genera automáticamente una solicitud de reabastecimiento.
5. Guardar el pedido.

### Ver pedido
Muestra el detalle completo: cliente, líneas de productos, estado, orden de producción asociada y notas. Desde aquí se puede cambiar el estado manualmente (marcar como entregado, cancelar).

---

## Módulo de Clientes

Acceso: menú lateral → **Recepción → Clientes**

Gestión del directorio de clientes. Permite:
- Buscar clientes por nombre, correo o teléfono.
- Crear, editar y desactivar clientes.
- Ver el historial de pedidos de cada cliente.

---

## Módulo de Inventario

### Productos
Acceso: menú lateral → **Inventario → Productos**

Catálogo completo de productos con información de costos y stock.

**Columnas de la tabla:**
- **Stock actual** — unidades físicas en almacén.
- **Reservado** — unidades comprometidas por pedidos activos.
- **Disponible** — stock actual menos reservado (unidades realmente libres).
- **Mínimo** — umbral de stock mínimo; al caer por debajo se genera una alerta.

**Estados de stock:**
- 🟢 **OK** — stock disponible por encima del mínimo.
- 🟡 **Stock bajo** — disponible igual o por debajo del mínimo.
- 🔴 **Sin stock** — disponible en cero o negativo.

**Exportar:**  botón **Exportar** → **Descargar PDF** o **Descargar CSV**.

### Movimientos de inventario
Acceso: menú lateral → **Inventario → Movimientos**

Registro de todas las entradas, salidas y ajustes de stock.

| Tipo | Cuándo se usa |
|------|---------------|
| Entrada | Recepción de mercancía, compra de materia prima |
| Salida | Ajuste manual de reducción de stock |
| Ajuste | Corrección de inventario por conteo físico |

> Al registrar una **entrada** el sistema crea automáticamente un lote FIFO con el costo unitario indicado.

En la parte superior aparece una sección de **Alertas de stock bajo** con los productos que requieren reabastecimiento.

**Exportar:** botón **Exportar** → PDF o CSV. El reporte incluye los movimientos del mes actual por defecto.

### Solicitudes de reabastecimiento
Acceso: menú lateral → **Inventario → Solicitudes** *(solo administrador)*

Las solicitudes se generan automáticamente en dos situaciones:
1. **Stock bajo mínimo** — cuando el stock disponible cae por debajo del mínimo configurado.
2. **Pedido bloqueado** — cuando un pedido no puede reservar stock por falta de inventario.

Se pueden filtrar por tipo (General / Vinculada a pedido) y gestionar su estado (pendiente, en proceso, recibido).

### Categorías
Acceso: menú lateral → **Inventario → Categorías** *(solo administrador)*

Administración de las categorías de productos. Cada producto pertenece a una categoría.

---

## Módulo de Producción

Acceso: menú lateral → **Producción → Órdenes**

### Panel de operarios
En la parte superior aparece una tarjeta por cada operario de producción mostrando:
- Su estado actual (Libre / En proceso / Pausado).
- La orden que tiene asignada (si aplica).
- El tiempo transcurrido en esa orden.
- La cantidad de órdenes en cola.

### Tabla de órdenes
Lista todas las órdenes de producción activas (no completadas). Cada fila muestra:
- Número de pedido relacionado.
- Cliente.
- Prioridad (Alta / Media / Baja).
- Estado actual.
- Operario asignado.
- **Tiempo** — duración con semáforo de color:
  - 🟢 Verde: menos de 2 horas.
  - 🟡 Amarillo: entre 2 y 4 horas.
  - 🔴 Rojo: más de 4 horas.

### Flujo de una orden
1. La orden aparece al crearse un pedido que pasa a producción.
2. **Asignar operario** — seleccionar el operario que realizará el trabajo. Si el operario ya tiene una orden activa, el sistema muestra un aviso informativo (la asignación sigue siendo posible).
3. **Iniciar** — registra la hora de inicio. Requiere tener un operario asignado. Si el operario ya tiene otra orden en proceso, el inicio queda bloqueado.
4. **Pausar / Reanudar** — disponible mientras la orden está en proceso.
5. **Completar** — finaliza la orden. El pedido pasa automáticamente a estado **Listo para entrega**.

---

## Módulo de Costos y Precios

### Valoración de inventario
Acceso: menú lateral → **Costos y Precios → Valoración**

Muestra el valor del inventario calculado con el método **FIFO** (First In, First Out): las unidades más antiguas se valúan primero.

**Tarjetas de resumen:** valor total FIFO, valor libre, valor reservado, lotes activos, productos con/sin stock.

**Tablas:**
- Valor por categoría — porcentaje de cada categoría sobre el total.
- Detalle por producto — costo promedio FIFO, valor FIFO, unidades y lotes.

**Exportar:** PDF o CSV con el estado actual del inventario.

### Lotes
Acceso: menú lateral → **Costos y Precios → Lotes**

Trazabilidad completa de cada entrada de stock como lote independiente.

**Columnas clave:**
- **Disponible** — unidades del lote que aún están en almacén.
- **Reservado** — unidades comprometidas a pedidos activos.
- **Libre** — disponible menos reservado.
- **Valor disponible** — disponible × costo unitario del lote.

**Estados de lote:** Disponible (verde) / Reservado (azul) / Agotado (gris).

**Exportar:** PDF o CSV con todos los lotes activos.

### Rentabilidad
Acceso: menú lateral → **Costos y Precios → Rentabilidad**

Análisis de márgenes con costos FIFO reales por producto y período.

**Filtros:** año y mes (opcional). Al no seleccionar mes se muestra el año completo.

**Columnas:**
- Unidades vendidas, precio promedio de venta.
- Costo promedio FIFO (costo real de las unidades consumidas).
- Ingresos, Costos, Ganancia bruta.
- **Margen %** — coloreado: verde ≥ 20 %, amarillo ≥ 10 %, rojo < 10 %.

**Exportar:** PDF o CSV del período seleccionado.

---

## Módulo de Administración

### Usuarios
Acceso: menú lateral → **Administración → Usuarios** *(solo administrador)*

Gestión de cuentas de usuario del sistema.

**Roles disponibles:**
- `administrador` — acceso total.
- `recepcionista` — pedidos, clientes, inventario básico.
- `produccion` — módulo de producción.

Al crear un usuario se define su nombre, correo, contraseña temporal y rol. El usuario puede cambiar su contraseña desde **Configuración de perfil**.

---

## Exportación de reportes

Los reportes PDF y CSV están disponibles en los módulos que muestran el botón **Exportar** en la esquina superior derecha.

| Reporte | Módulo | Formato |
|---------|--------|---------|
| Productos | Inventario → Productos | PDF / CSV |
| Movimientos | Inventario → Movimientos | PDF / CSV |
| Valoración FIFO | Costos → Valoración | PDF / CSV |
| Lotes activos | Costos → Lotes | PDF / CSV |
| Rentabilidad | Costos → Rentabilidad | PDF / CSV |

Los PDF incluyen encabezado con nombre del sistema, fecha de generación y tarjetas de resumen.

---

## Modo claro / oscuro

El botón **Modo claro / Modo oscuro** se encuentra en la parte inferior del menú lateral. La preferencia se guarda automáticamente para futuras sesiones.

---

## Soporte

Para soporte técnico o reportar errores contactar al administrador del sistema.
