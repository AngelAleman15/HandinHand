-- Agrega el atributo 'condicion' (estado de desgaste) a la tabla productos
-- y actualiza el listado para mostrarlo en mis-productos.php

ALTER TABLE productos 
ADD COLUMN condicion ENUM('nuevo','como nuevo','poco uso','usado','muy desgastado') DEFAULT 'usado' AFTER estado;

-- Ejemplo de uso:
-- UPDATE productos SET condicion = 'nuevo' WHERE id = 1;
-- SELECT nombre, estado, condicion FROM productos;
