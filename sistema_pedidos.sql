-- ============================================================
-- SISTEMA DE PEDIDOS - Base de datos MariaDB
-- ============================================================
-- Módulos: Usuarios, Clientes, Pedidos, Inventario,
--          Costos/Precios, Producción, Dashboard
-- ============================================================

CREATE DATABASE IF NOT EXISTS sistema_pedidos
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE sistema_pedidos;

-- ============================================================
-- MÓDULO DE USUARIOS
-- ============================================================

CREATE TABLE usuarios (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    nombre        VARCHAR(100) NOT NULL,
    email         VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rol           ENUM('administrador','recepcionista','produccion') NOT NULL DEFAULT 'recepcionista',
    activo        TINYINT(1) NOT NULL DEFAULT 1,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB COMMENT='Gestión de accesos y roles del sistema';

-- ============================================================
-- MÓDULO DE CLIENTES
-- ============================================================

CREATE TABLE clientes (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    nombre     VARCHAR(150) NOT NULL,
    telefono   VARCHAR(20),
    email      VARCHAR(150),
    direccion  TEXT,
    notas      TEXT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_nombre (nombre),
    INDEX idx_telefono (telefono)
) ENGINE=InnoDB COMMENT='Registro de clientes y datos de contacto';

-- ============================================================
-- MÓDULO DE CATEGORÍAS
-- ============================================================

CREATE TABLE categorias (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(80)  NOT NULL UNIQUE,
    descripcion TEXT,
    activo      TINYINT(1) NOT NULL DEFAULT 1,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB COMMENT='Categorías de productos';

-- ============================================================
-- MÓDULO DE INVENTARIO — Productos
-- ============================================================

CREATE TABLE productos (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    nombre           VARCHAR(150) NOT NULL,
    categoria_id     BIGINT UNSIGNED NOT NULL,
    descripcion      TEXT,
    costo_base       DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Costo de producción/compra',
    margen_ganancia  DECIMAL(5,2)  NOT NULL DEFAULT 0.00 COMMENT 'Porcentaje de margen (%)',
    precio_venta     DECIMAL(10,2) GENERATED ALWAYS AS
                       (ROUND(costo_base * (1 + margen_ganancia / 100), 2)) STORED
                       COMMENT 'Calculado automáticamente: costo_base * (1 + margen%)',
    stock_actual     INT NOT NULL DEFAULT 0,
    stock_reservado  INT NOT NULL DEFAULT 0,
    stock_minimo     INT NOT NULL DEFAULT 5  COMMENT 'Umbral para alerta de stock bajo',
    activo           TINYINT(1) NOT NULL DEFAULT 1,
    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_producto_categoria FOREIGN KEY (categoria_id) REFERENCES categorias(id),
    INDEX idx_categoria_id (categoria_id),
    INDEX idx_stock (stock_actual, stock_minimo)
) ENGINE=InnoDB COMMENT='Catálogo de productos con costos y precios calculados';

-- ============================================================
-- MÓDULO DE PEDIDOS
-- ============================================================

CREATE TABLE pedidos (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id     INT NOT NULL,
    usuario_id     INT NOT NULL COMMENT 'Recepcionista que registró el pedido',
    estado         ENUM('pendiente','en_produccion','listo','entregado','cancelado')
                   NOT NULL DEFAULT 'pendiente',
    subtotal       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    descuento      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total          DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total_costo    DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Costo total de producción',
    ganancia       DECIMAL(10,2) GENERATED ALWAYS AS (total - total_costo) STORED,
    notas          TEXT,
    fecha_pedido   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_prometida DATE COMMENT 'Fecha de entrega acordada con el cliente',
    fecha_entrega  TIMESTAMP NULL COMMENT 'Fecha real de entrega',
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_pedido_cliente  FOREIGN KEY (cliente_id) REFERENCES clientes(id),
    CONSTRAINT fk_pedido_usuario  FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    INDEX idx_estado      (estado),
    INDEX idx_cliente     (cliente_id),
    INDEX idx_fecha       (fecha_pedido)
) ENGINE=InnoDB COMMENT='Registro y seguimiento de pedidos';

CREATE TABLE detalle_pedido (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id         INT NOT NULL,
    producto_id       INT NOT NULL,
    cantidad          INT NOT NULL DEFAULT 1,
    precio_unitario   DECIMAL(10,2) NOT NULL COMMENT 'Precio al momento del pedido',
    costo_unitario    DECIMAL(10,2) NOT NULL COMMENT 'Costo al momento del pedido',
    subtotal          DECIMAL(10,2) GENERATED ALWAYS AS (cantidad * precio_unitario) STORED,
    descripcion_custom TEXT COMMENT 'Personalización del producto (color, texto, diseño)',
    CONSTRAINT fk_detalle_pedido   FOREIGN KEY (pedido_id)   REFERENCES pedidos(id)   ON DELETE CASCADE,
    CONSTRAINT fk_detalle_producto FOREIGN KEY (producto_id) REFERENCES productos(id),
    INDEX idx_pedido   (pedido_id),
    INDEX idx_producto (producto_id)
) ENGINE=InnoDB COMMENT='Líneas de detalle por pedido';

-- ============================================================
-- MÓDULO DE PRODUCCIÓN
-- ============================================================

CREATE TABLE ordenes_produccion (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id       INT NOT NULL UNIQUE COMMENT 'Un pedido genera una sola orden de producción',
    usuario_id      INT COMMENT 'Operario de producción asignado',
    estado          ENUM('asignado','en_proceso','completado','pausado')
                    NOT NULL DEFAULT 'asignado',
    prioridad       TINYINT NOT NULL DEFAULT 2 COMMENT '1=Alta 2=Normal 3=Baja',
    fecha_inicio    TIMESTAMP NULL,
    fecha_fin       TIMESTAMP NULL,
    tiempo_minutos  INT GENERATED ALWAYS AS
                      (TIMESTAMPDIFF(MINUTE, fecha_inicio, fecha_fin)) STORED
                      COMMENT 'Tiempo real de producción en minutos',
    observaciones   TEXT,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_op_pedido  FOREIGN KEY (pedido_id)  REFERENCES pedidos(id),
    CONSTRAINT fk_op_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    INDEX idx_estado    (estado),
    INDEX idx_prioridad (prioridad)
) ENGINE=InnoDB COMMENT='Órdenes de producción generadas desde pedidos';

-- ============================================================
-- MÓDULO DE INVENTARIO — Movimientos
-- ============================================================

CREATE TABLE movimientos_inventario (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    producto_id    INT NOT NULL,
    usuario_id     INT NOT NULL,
    pedido_id      INT COMMENT 'Asociado si el movimiento es por un pedido',
    tipo           ENUM('entrada','salida','ajuste') NOT NULL,
    cantidad       INT NOT NULL COMMENT 'Positivo=entrada, negativo=salida',
    costo_unitario DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    motivo         VARCHAR(200) COMMENT 'Compra, Uso en pedido, Ajuste, etc.',
    fecha          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_mov_producto FOREIGN KEY (producto_id) REFERENCES productos(id),
    CONSTRAINT fk_mov_usuario  FOREIGN KEY (usuario_id)  REFERENCES usuarios(id),
    CONSTRAINT fk_mov_pedido   FOREIGN KEY (pedido_id)   REFERENCES pedidos(id),
    INDEX idx_producto (producto_id),
    INDEX idx_tipo     (tipo),
    INDEX idx_fecha    (fecha)
) ENGINE=InnoDB COMMENT='Historial de entradas y salidas de inventario';

-- ============================================================
-- MÓDULO DE ALERTAS DE STOCK
-- ============================================================

CREATE TABLE alertas_stock (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    producto_id      INT NOT NULL,
    stock_al_generar INT NOT NULL,
    stock_minimo     INT NOT NULL,
    resuelta         TINYINT(1) NOT NULL DEFAULT 0,
    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resuelta_at      TIMESTAMP NULL,
    CONSTRAINT fk_alerta_producto FOREIGN KEY (producto_id) REFERENCES productos(id),
    INDEX idx_resuelta  (resuelta),
    INDEX idx_producto  (producto_id)
) ENGINE=InnoDB COMMENT='Alertas automáticas de stock bajo por producto';

-- ============================================================
-- TRIGGERS
-- ============================================================

DELIMITER $$

-- 1. Al insertar detalle de pedido: actualizar totales del pedido
CREATE TRIGGER trg_detalle_insert
AFTER INSERT ON detalle_pedido
FOR EACH ROW
BEGIN
    UPDATE pedidos
    SET subtotal    = (SELECT COALESCE(SUM(cantidad * precio_unitario), 0) FROM detalle_pedido WHERE pedido_id = NEW.pedido_id),
        total_costo = (SELECT COALESCE(SUM(cantidad * costo_unitario), 0)  FROM detalle_pedido WHERE pedido_id = NEW.pedido_id),
        total       = subtotal - descuento
    WHERE id = NEW.pedido_id;
END$$

-- 2. Al eliminar detalle de pedido: actualizar totales del pedido
CREATE TRIGGER trg_detalle_delete
AFTER DELETE ON detalle_pedido
FOR EACH ROW
BEGIN
    UPDATE pedidos
    SET subtotal    = (SELECT COALESCE(SUM(cantidad * precio_unitario), 0) FROM detalle_pedido WHERE pedido_id = OLD.pedido_id),
        total_costo = (SELECT COALESCE(SUM(cantidad * costo_unitario), 0)  FROM detalle_pedido WHERE pedido_id = OLD.pedido_id),
        total       = subtotal - descuento
    WHERE id = OLD.pedido_id;
END$$

-- 3. Al insertar movimiento de inventario: actualizar stock, crear lote en entradas
--    y liberar pedidos bloqueados cuando el stock es suficiente
CREATE TRIGGER trg_movimiento_insert
AFTER INSERT ON movimientos_inventario
FOR EACH ROW
BEGIN
    DECLARE v_pedido_id INT;
    DECLARE done        INT DEFAULT 0;

    UPDATE productos SET stock_actual = stock_actual + NEW.cantidad WHERE id = NEW.producto_id;

    -- Crear lote automáticamente para cada entrada
    IF NEW.tipo = 'entrada' THEN
        INSERT INTO lotes (producto_id, movimiento_id, numero_lote, fecha_entrada,
                           cantidad_inicial, cantidad_disponible, costo_unitario)
        VALUES (NEW.producto_id, NEW.id,
                CONCAT('L-', YEAR(NEW.fecha), '-', LPAD(NEW.id, 6, '0')),
                NEW.fecha, NEW.cantidad, NEW.cantidad, NEW.costo_unitario);
    END IF;

    IF (SELECT stock_actual FROM productos WHERE id = NEW.producto_id) <
       (SELECT stock_minimo  FROM productos WHERE id = NEW.producto_id) THEN
        INSERT INTO alertas_stock (producto_id, stock_al_generar, stock_minimo, cantidad_faltante)
        SELECT id, stock_actual, stock_minimo, 0
          FROM productos WHERE id = NEW.producto_id
           AND NOT EXISTS (
               SELECT 1 FROM alertas_stock
                WHERE producto_id = NEW.producto_id AND resuelta = 0 AND pedido_id IS NULL
           );
    END IF;

    IF NEW.tipo = 'entrada' THEN
        BEGIN
            DECLARE cur_pedidos CURSOR FOR
                SELECT DISTINCT dp.pedido_id FROM detalle_pedido dp
                  JOIN pedidos p ON p.id = dp.pedido_id
                 WHERE dp.producto_id = NEW.producto_id AND p.estado = 'esperando_stock';
            DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
            OPEN cur_pedidos;
            loop_pedidos: LOOP
                FETCH cur_pedidos INTO v_pedido_id;
                IF done = 1 THEN LEAVE loop_pedidos; END IF;
                SELECT COUNT(*) INTO @sin_stock
                  FROM detalle_pedido dp JOIN productos pr ON pr.id = dp.producto_id
                 WHERE dp.pedido_id = v_pedido_id AND (pr.stock_actual - pr.stock_reservado) < dp.cantidad;
                IF @sin_stock = 0 THEN
                    -- Liberar pedido (dispara trg_pedido_estado → asigna lotes FIFO)
                    UPDATE pedidos SET estado = 'pendiente' WHERE id = v_pedido_id;
                    UPDATE productos pr JOIN detalle_pedido dp ON dp.producto_id = pr.id
                       SET pr.stock_reservado = pr.stock_reservado + dp.cantidad
                     WHERE dp.pedido_id = v_pedido_id;
                    UPDATE alertas_stock SET resuelta = 1, resuelta_at = CURRENT_TIMESTAMP
                     WHERE pedido_id = v_pedido_id AND resuelta = 0;
                    UPDATE solicitudes_reabastecimiento SET estado = 'recibido'
                     WHERE pedido_id = v_pedido_id AND estado != 'cancelado';
                END IF;
            END LOOP;
            CLOSE cur_pedidos;
        END;
    END IF;
END$$

-- 4. Al cambiar estado del pedido:
--    - en_produccion: crear orden de producción
--    - pendiente (desde esperando_stock): asignar lotes FIFO
--    - entregado: consumir lotes
--    - cancelado: liberar lotes y stock reservado
CREATE TRIGGER trg_pedido_estado
AFTER UPDATE ON pedidos
FOR EACH ROW
BEGIN
    IF NEW.estado = 'en_produccion' AND OLD.estado != 'en_produccion' THEN
        INSERT IGNORE INTO ordenes_produccion (pedido_id, estado) VALUES (NEW.id, 'asignado');
    END IF;

    IF NEW.estado = 'pendiente' AND OLD.estado = 'esperando_stock' THEN
        CALL sp_asignar_lotes_fifo(NEW.id);
    END IF;

    IF NEW.estado = 'entregado' AND OLD.estado != 'entregado' THEN
        UPDATE lotes l
          JOIN detalle_pedido_lotes dpl ON dpl.lote_id = l.id
          JOIN detalle_pedido dp ON dp.id = dpl.detalle_pedido_id
           SET l.cantidad_disponible = l.cantidad_disponible - dpl.cantidad_asignada,
               l.cantidad_reservada  = GREATEST(l.cantidad_reservada - dpl.cantidad_asignada, 0)
         WHERE dp.pedido_id = NEW.id;
    END IF;

    IF NEW.estado = 'cancelado' AND OLD.estado != 'cancelado' THEN
        IF OLD.estado IN ('pendiente', 'en_produccion') THEN
            UPDATE lotes l
              JOIN detalle_pedido_lotes dpl ON dpl.lote_id = l.id
              JOIN detalle_pedido dp ON dp.id = dpl.detalle_pedido_id
               SET l.cantidad_reservada = GREATEST(l.cantidad_reservada - dpl.cantidad_asignada, 0)
             WHERE dp.pedido_id = NEW.id;
            UPDATE productos pr JOIN detalle_pedido dp ON dp.producto_id = pr.id
               SET pr.stock_reservado = GREATEST(pr.stock_reservado - dp.cantidad, 0)
             WHERE dp.pedido_id = NEW.id;
        END IF;
        UPDATE alertas_stock SET resuelta = 1, resuelta_at = CURRENT_TIMESTAMP
         WHERE pedido_id = NEW.id AND resuelta = 0;
        UPDATE solicitudes_reabastecimiento SET estado = 'cancelado'
         WHERE pedido_id = NEW.id AND estado = 'pendiente';
    END IF;
END$$

-- 5. Al completar orden de producción: actualizar estado del pedido a 'listo'
CREATE TRIGGER trg_op_completada
AFTER UPDATE ON ordenes_produccion
FOR EACH ROW
BEGIN
    IF NEW.estado = 'completado' AND OLD.estado != 'completado' THEN
        UPDATE pedidos SET estado = 'listo' WHERE id = NEW.pedido_id;
    END IF;
END$$

DELIMITER ;

-- ============================================================
-- VISTAS PARA EL DASHBOARD
-- ============================================================

-- Pedidos activos con información completa
CREATE VIEW v_pedidos_activos AS
SELECT
    p.id,
    p.estado,
    c.nombre        AS cliente,
    c.telefono      AS cliente_telefono,
    u.nombre        AS recepcionista,
    p.total,
    p.ganancia,
    p.fecha_pedido,
    p.fecha_prometida,
    p.notas,
    op.estado       AS estado_produccion,
    op.usuario_id   AS operario_id
FROM pedidos p
JOIN clientes  c ON c.id = p.cliente_id
JOIN usuarios  u ON u.id = p.usuario_id
LEFT JOIN ordenes_produccion op ON op.pedido_id = p.id
WHERE p.estado NOT IN ('entregado','cancelado')
ORDER BY p.fecha_prometida ASC, p.id ASC;

-- Resumen del dashboard administrativo
CREATE VIEW v_dashboard AS
SELECT
    (SELECT COUNT(*) FROM pedidos  WHERE estado NOT IN ('entregado','cancelado')) AS pedidos_activos,
    (SELECT COUNT(*) FROM pedidos  WHERE estado = 'pendiente')                   AS pedidos_pendientes,
    (SELECT COUNT(*) FROM pedidos  WHERE estado = 'en_produccion')               AS en_produccion,
    (SELECT COUNT(*) FROM pedidos  WHERE estado = 'listo')                       AS listos_para_entrega,
    (SELECT COUNT(*) FROM pedidos  WHERE estado = 'entregado'
       AND DATE(fecha_entrega) = CURDATE())                                      AS entregados_hoy,
    (SELECT COALESCE(SUM(total),0)    FROM pedidos WHERE estado = 'entregado'
       AND MONTH(fecha_entrega) = MONTH(CURDATE())
       AND YEAR(fecha_entrega)  = YEAR(CURDATE()))                               AS ventas_mes,
    (SELECT COALESCE(SUM(ganancia),0) FROM pedidos WHERE estado = 'entregado'
       AND MONTH(fecha_entrega) = MONTH(CURDATE())
       AND YEAR(fecha_entrega)  = YEAR(CURDATE()))                               AS ganancia_mes,
    (SELECT COUNT(*) FROM alertas_stock WHERE resuelta = 0)                      AS alertas_stock_activas,
    (SELECT COUNT(*) FROM productos    WHERE stock_actual <= stock_minimo
       AND activo = 1)                                                           AS productos_bajo_stock;

-- Historial completo de pedidos por cliente
CREATE VIEW v_historial_cliente AS
SELECT
    c.id            AS cliente_id,
    c.nombre        AS cliente,
    c.telefono,
    c.email,
    COUNT(p.id)                                 AS total_pedidos,
    COALESCE(SUM(p.total),0)                    AS total_gastado,
    MAX(p.fecha_pedido)                         AS ultimo_pedido,
    COALESCE(AVG(p.total),0)                    AS ticket_promedio
FROM clientes c
LEFT JOIN pedidos p ON p.cliente_id = c.id
GROUP BY c.id, c.nombre, c.telefono, c.email;

-- Productos con alerta de stock
CREATE VIEW v_alertas_inventario AS
SELECT
    p.id,
    p.nombre,
    c.nombre     AS categoria,
    p.stock_actual,
    p.stock_minimo,
    (p.stock_minimo - p.stock_actual) AS unidades_faltantes,
    a.created_at AS alerta_desde
FROM productos p
JOIN categorias c ON c.id = p.categoria_id
JOIN alertas_stock a ON a.producto_id = p.id AND a.resuelta = 0
WHERE p.activo = 1;

-- Rendimiento de producción
CREATE VIEW v_rendimiento_produccion AS
SELECT
    u.id,
    u.nombre,
    COUNT(op.id)                               AS ordenes_completadas,
    COALESCE(AVG(op.tiempo_minutos),0)         AS tiempo_promedio_min,
    COALESCE(SUM(p.total),0)                   AS valor_producido
FROM usuarios u
LEFT JOIN ordenes_produccion op ON op.usuario_id = u.id AND op.estado = 'completado'
LEFT JOIN pedidos p ON p.id = op.pedido_id
WHERE u.rol = 'produccion'
GROUP BY u.id, u.nombre;

-- ============================================================
-- MÓDULO DE COSTOS Y PRECIOS — Lotes e inventario FIFO
-- ============================================================

-- Lotes: una fila por cada entrada de inventario registrada.
-- El trigger trg_movimiento_insert crea el lote automáticamente
-- al insertar un movimiento de tipo 'entrada'.
CREATE TABLE lotes (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    producto_id         INT NOT NULL,
    movimiento_id       INT NOT NULL COMMENT 'Movimiento de entrada que generó el lote',
    numero_lote         VARCHAR(30) NOT NULL UNIQUE COMMENT 'L-YYYY-NNNNNN',
    fecha_entrada       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    cantidad_inicial    INT NOT NULL COMMENT 'Unidades al crear el lote',
    cantidad_disponible INT NOT NULL COMMENT 'Inicial menos consumido; decrece al entregar pedidos',
    cantidad_reservada  INT NOT NULL DEFAULT 0
        COMMENT 'Subset de disponible comprometido a pedidos pendientes/en producción',
    costo_unitario      DECIMAL(10,2) NOT NULL,
    activo              TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT fk_lote_producto   FOREIGN KEY (producto_id)   REFERENCES productos(id),
    CONSTRAINT fk_lote_movimiento FOREIGN KEY (movimiento_id) REFERENCES movimientos_inventario(id),
    INDEX idx_lotes_prod_fecha (producto_id, fecha_entrada)
) ENGINE=InnoDB COMMENT='Trazabilidad FIFO de entradas de inventario por producto';

-- Asignación de lotes FIFO a líneas de pedido.
-- Generada por sp_asignar_lotes_fifo al confirmar stock del pedido.
CREATE TABLE detalle_pedido_lotes (
    id                 INT AUTO_INCREMENT PRIMARY KEY,
    detalle_pedido_id  INT NOT NULL,
    lote_id            INT NOT NULL,
    cantidad_asignada  INT NOT NULL,
    costo_unitario     DECIMAL(10,2) NOT NULL COMMENT 'Snapshot del costo del lote al asignar',
    CONSTRAINT fk_dpl_detalle FOREIGN KEY (detalle_pedido_id) REFERENCES detalle_pedido(id) ON DELETE CASCADE,
    CONSTRAINT fk_dpl_lote    FOREIGN KEY (lote_id)           REFERENCES lotes(id),
    INDEX idx_dpl_detalle (detalle_pedido_id),
    INDEX idx_dpl_lote    (lote_id)
) ENGINE=InnoDB COMMENT='Asignación FIFO de lotes a líneas de pedido';

-- ============================================================
-- STORED PROCEDURE — Asignación FIFO de lotes a un pedido
-- ============================================================

DELIMITER $$

-- Itera las líneas del pedido, asigna los lotes más antiguos con
-- stock libre, crea registros en detalle_pedido_lotes y actualiza
-- el costo_unitario de cada línea con el costo promedio ponderado FIFO.
-- También recalcula total_costo del pedido.
CREATE PROCEDURE sp_asignar_lotes_fifo(IN p_pedido_id INT)
BEGIN
    DECLARE done       INT DEFAULT 0;
    DECLARE v_det_id   INT;
    DECLARE v_prod_id  INT;
    DECLARE v_cantidad INT;

    DECLARE cur_detalles CURSOR FOR
        SELECT id, producto_id, cantidad
        FROM detalle_pedido
        WHERE pedido_id = p_pedido_id;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

    OPEN cur_detalles;
    det_loop: LOOP
        FETCH cur_detalles INTO v_det_id, v_prod_id, v_cantidad;
        IF done = 1 THEN LEAVE det_loop; END IF;

        BEGIN
            DECLARE v_restante    INT;
            DECLARE v_costo_total DECIMAL(10,2);
            DECLARE v_asignado    INT;
            DECLARE v_lote_id     INT;
            DECLARE v_libre       INT;
            DECLARE v_lote_costo  DECIMAL(10,2);
            DECLARE v_consumir    INT;

            SET v_restante    = v_cantidad;
            SET v_costo_total = 0;
            SET v_asignado    = 0;

            DELETE FROM detalle_pedido_lotes WHERE detalle_pedido_id = v_det_id;

            lote_loop: LOOP
                IF v_restante <= 0 THEN LEAVE lote_loop; END IF;

                SET v_lote_id = NULL;

                SELECT id,
                       (cantidad_disponible - cantidad_reservada),
                       costo_unitario
                INTO v_lote_id, v_libre, v_lote_costo
                FROM lotes
                WHERE producto_id = v_prod_id
                  AND (cantidad_disponible - cantidad_reservada) > 0
                  AND activo = 1
                ORDER BY fecha_entrada ASC
                LIMIT 1;

                IF v_lote_id IS NULL THEN LEAVE lote_loop; END IF;

                SET v_consumir = LEAST(v_libre, v_restante);

                INSERT INTO detalle_pedido_lotes
                    (detalle_pedido_id, lote_id, cantidad_asignada, costo_unitario)
                VALUES
                    (v_det_id, v_lote_id, v_consumir, v_lote_costo);

                UPDATE lotes
                   SET cantidad_reservada = cantidad_reservada + v_consumir
                 WHERE id = v_lote_id;

                SET v_costo_total = v_costo_total + (v_consumir * v_lote_costo);
                SET v_asignado    = v_asignado + v_consumir;
                SET v_restante    = v_restante - v_consumir;
            END LOOP;

            IF v_asignado > 0 THEN
                UPDATE detalle_pedido
                   SET costo_unitario = ROUND(v_costo_total / v_asignado, 2)
                 WHERE id = v_det_id;
            END IF;
        END;
    END LOOP;
    CLOSE cur_detalles;

    UPDATE pedidos
       SET total_costo = (
           SELECT COALESCE(SUM(cantidad * costo_unitario), 0)
             FROM detalle_pedido
            WHERE pedido_id = p_pedido_id
       )
     WHERE id = p_pedido_id;
END$$

DELIMITER ;

-- Lotes con disponibilidad y valor calculado
CREATE VIEW v_lotes_activos AS
SELECT
    l.id, l.numero_lote,
    p.id   AS producto_id, p.nombre AS producto, c.nombre AS categoria,
    l.fecha_entrada, l.cantidad_inicial, l.cantidad_disponible, l.cantidad_reservada,
    (l.cantidad_disponible - l.cantidad_reservada) AS cantidad_libre,
    l.costo_unitario,
    (l.cantidad_disponible * l.costo_unitario)     AS valor_disponible,
    l.activo, l.movimiento_id
FROM lotes l
JOIN productos p ON p.id = l.producto_id
JOIN categorias c ON c.id = p.categoria_id
ORDER BY l.producto_id, l.fecha_entrada ASC;

-- Valoración FIFO del inventario por producto
CREATE VIEW v_valoracion_inventario AS
SELECT
    p.id AS producto_id, p.nombre AS producto, c.nombre AS categoria,
    p.stock_actual, p.stock_reservado, (p.stock_actual - p.stock_reservado) AS stock_libre,
    COALESCE(SUM(l.cantidad_disponible), 0)                             AS unidades_en_lotes,
    COALESCE(SUM(l.cantidad_disponible * l.costo_unitario), 0)          AS valor_total_fifo,
    COALESCE(SUM(l.cantidad_disponible * l.costo_unitario) /
             NULLIF(SUM(l.cantidad_disponible), 0), p.costo_base)       AS costo_promedio_fifo,
    COUNT(l.id)                                                          AS lotes_activos
FROM productos p
JOIN categorias c ON c.id = p.categoria_id
LEFT JOIN lotes l ON l.producto_id = p.id AND l.activo = 1 AND l.cantidad_disponible > 0
WHERE p.activo = 1
GROUP BY p.id, p.nombre, c.nombre, p.stock_actual, p.stock_reservado, p.costo_base
ORDER BY c.nombre, p.nombre;

-- Rentabilidad por producto, año y mes (solo pedidos entregados)
CREATE VIEW v_rentabilidad_productos AS
SELECT
    pr.id AS producto_id, pr.nombre AS producto, c.nombre AS categoria,
    YEAR(pe.fecha_entrega) AS anio, MONTH(pe.fecha_entrega) AS mes,
    COUNT(DISTINCT pe.id)                                                  AS pedidos_con_producto,
    SUM(dp.cantidad)                                                       AS unidades_vendidas,
    ROUND(AVG(dp.precio_unitario), 2)                                      AS precio_promedio_venta,
    ROUND(AVG(dp.costo_unitario), 2)                                       AS costo_promedio_fifo,
    SUM(dp.cantidad * dp.precio_unitario)                                  AS ingresos,
    SUM(dp.cantidad * dp.costo_unitario)                                   AS costos,
    SUM(dp.cantidad * (dp.precio_unitario - dp.costo_unitario))            AS ganancia_bruta,
    ROUND(SUM(dp.cantidad * (dp.precio_unitario - dp.costo_unitario)) /
          NULLIF(SUM(dp.cantidad * dp.precio_unitario), 0) * 100, 2)      AS margen_pct
FROM productos pr
JOIN categorias c      ON c.id = pr.categoria_id
JOIN detalle_pedido dp ON dp.producto_id = pr.id
JOIN pedidos pe        ON pe.id = dp.pedido_id AND pe.estado = 'entregado'
GROUP BY pr.id, pr.nombre, c.nombre, YEAR(pe.fecha_entrega), MONTH(pe.fecha_entrega)
ORDER BY c.nombre, pr.nombre, anio DESC, mes DESC;

-- ============================================================
-- DATOS INICIALES
-- ============================================================

-- Usuario administrador por defecto (contraseña: cambiar en producción)
INSERT INTO usuarios (nombre, email, password_hash, rol) VALUES
('Administrador', 'admin@sistema.local', SHA2('Admin123!',256), 'administrador');

-- Categorías base
INSERT INTO categorias (nombre, descripcion) VALUES
('Tazas',      'Tazas cerámicas y de colores para sublimación e impresión personalizada.'),
('Camisetas',  'Prendas de algodón y poliéster para transfer, sublimación y bordado.'),
('Lapiceros',  'Bolígrafos metálicos y plásticos para grabado láser y serigrafía.'),
('Viniles',    'Láminas de vinil adhesivo y transfer para corte en plotter y prensa.'),
('Bolsas',     'Tote bags y bolsas de lienzo para serigrafía y sublimación.'),
('Gorras',     'Gorras y sombreros para sublimación y bordado computarizado.'),
('Accesorios', 'Mousepads, termos, llaveros y otros artículos promocionales sublimables.');

-- Productos de ejemplo (referenciando categorias por id)
INSERT INTO productos (nombre, categoria_id, costo_base, margen_ganancia, stock_actual, stock_minimo)
SELECT nombre, c.id, costo_base, margen_ganancia, stock_actual, stock_minimo
FROM (
    SELECT 'Taza cerámica blanca 11oz'  AS nombre, 'Tazas'      AS cat,  5.00 AS costo_base, 120.00 AS margen_ganancia,  50 AS stock_actual, 10 AS stock_minimo
    UNION ALL
    SELECT 'Taza mágica cambia color',           'Tazas',      8.00, 100.00,  30,  8
    UNION ALL
    SELECT 'Camiseta algodón talla M',           'Camisetas',  7.00, 100.00,  40, 10
    UNION ALL
    SELECT 'Camiseta algodón talla L',           'Camisetas',  7.00, 100.00,  40, 10
    UNION ALL
    SELECT 'Lapicero metálico grabado',          'Lapiceros',  1.50, 150.00, 100, 20
    UNION ALL
    SELECT 'Vinil adhesivo A4',                  'Viniles',    0.80, 200.00, 200, 30
    UNION ALL
    SELECT 'Vinil transfer textil',              'Viniles',    1.20, 180.00, 150, 25
    UNION ALL
    SELECT 'Tote bag lienzo',                    'Bolsas',     4.00, 125.00,  35,  8
    UNION ALL
    SELECT 'Gorra bordada ajustable',            'Gorras',     6.00, 110.00,  25,  5
    UNION ALL
    SELECT 'Mousepad sublimado 20x24cm',         'Accesorios', 3.50, 130.00,  45, 10
) AS datos
JOIN categorias c ON c.nombre = datos.cat;

-- ============================================================
-- FIN DEL SCRIPT
-- ============================================================
