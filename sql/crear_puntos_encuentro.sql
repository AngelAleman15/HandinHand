-- Sistema de Puntos de Encuentro para productos
-- Permite al vendedor establecer ubicaciones específicas para realizar el trueque

USE handinhand;

-- Tabla de puntos de encuentro
CREATE TABLE IF NOT EXISTS `puntos_encuentro` (
  `id` int NOT NULL AUTO_INCREMENT,
  `producto_id` int NOT NULL COMMENT 'Producto al que pertenece el punto de encuentro',
  `nombre` varchar(255) NOT NULL COMMENT 'Nombre del lugar (ej: "Starbucks Centro", "Plaza Principal")',
  `descripcion` text COMMENT 'Descripción adicional del lugar',
  `direccion` varchar(500) NOT NULL COMMENT 'Dirección completa',
  `latitud` decimal(10, 8) NOT NULL COMMENT 'Latitud GPS',
  `longitud` decimal(11, 8) NOT NULL COMMENT 'Longitud GPS',
  `referencia` varchar(255) COMMENT 'Punto de referencia (ej: "Frente al banco")',
  `horario_sugerido` varchar(255) COMMENT 'Horario sugerido (ej: "Lun-Vie 9am-6pm")',
  `es_principal` tinyint(1) DEFAULT 0 COMMENT '1 si es el punto principal/preferido',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `producto_id` (`producto_id`),
  CONSTRAINT `fk_punto_encuentro_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar puntos de encuentro de ejemplo
-- URUGUAY - Montevideo y ciudades principales

INSERT INTO `puntos_encuentro` (`producto_id`, `nombre`, `descripcion`, `direccion`, `latitud`, `longitud`, `referencia`, `horario_sugerido`, `es_principal`) VALUES
-- Producto 1: Smartphone Samsung - Montevideo
(1, 'Starbucks Montevideo Shopping', 'Centro comercial céntrico y seguro con cámaras', 'Montevideo Shopping, Av. Luis Alberto de Herrera 1290, Montevideo', -34.90166700, -56.16277800, 'Planta baja, cerca de la entrada principal', 'Lun-Dom 10am-9pm', 1),
(1, 'Plaza Independencia', 'Plaza céntrica con mucha vigilancia policial', 'Plaza Independencia, Ciudad Vieja, Montevideo', -34.90694400, -56.20138900, 'Frente a la Torre Ejecutiva', 'Lun-Dom 9am-7pm (solo de día)', 0),
(1, 'McDonald\'s Tres Cruces', 'Terminal de ómnibus con alta seguridad', 'Terminal Tres Cruces, Bulevar Artigas 1825, Montevideo', -34.89444400, -56.16666700, 'Food court, segundo nivel', 'Lun-Dom 7am-11pm', 0),

-- Producto 2: Zapatillas Nike - Montevideo y Maldonado
(2, 'Rambla de Montevideo', 'Paseo costero seguro y público', 'Rambla República del Perú, Pocitos, Montevideo', -34.91166700, -56.15833300, 'Altura del Puertito del Buceo', 'Lun-Dom 8am-7pm', 1),
(2, 'Punta Carretas Shopping', 'Centro comercial de alto nivel con seguridad', 'Punta Carretas Shopping, Ellauri 350, Montevideo', -34.91944400, -56.16111100, 'Patio de comidas, planta alta', 'Lun-Dom 10am-10pm', 0),
(2, 'Gorlero - Punta del Este', 'Avenida principal turística en Punta del Este', 'Av. Gorlero, Punta del Este, Maldonado', -34.96388900, -54.94777800, 'Frente a la plaza Artigas', 'Lun-Dom 10am-8pm', 0),

-- Producto 3: Guitarra acústica - Varias ciudades
(3, 'Parque Rodó', 'Parque público con buena iluminación y tránsito', 'Parque Rodó, Av. Sarmiento, Montevideo', -34.91527800, -56.16583300, 'Entrada principal, junto al Teatro de Verano', 'Lun-Dom 9am-7pm (solo de día)', 1),
(3, 'Nuevocentro Shopping', 'Centro comercial en zona norte de Montevideo', 'Nuevocentro Shopping, Av. Luis Alberto de Herrera 3365, Montevideo', -34.88305600, -56.13472200, 'Food court, planta baja', 'Lun-Dom 10am-10pm', 0),
(3, 'Plaza Artigas - Colonia', 'Plaza principal de Colonia del Sacramento', 'Plaza Artigas, Colonia del Sacramento', -34.46305600, -57.84083300, 'Centro histórico, frente a la Iglesia', 'Lun-Dom 9am-6pm', 0);

-- Verificación
SELECT 
    pe.id,
    p.nombre as producto,
    pe.nombre as punto_encuentro,
    pe.direccion,
    pe.latitud,
    pe.longitud,
    pe.es_principal
FROM puntos_encuentro pe
JOIN productos p ON pe.producto_id = p.id
ORDER BY pe.producto_id, pe.es_principal DESC;

SELECT 'Tabla de puntos de encuentro creada exitosamente!' as Resultado;
