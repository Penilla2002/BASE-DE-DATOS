-- Insertar categorías primero
INSERT INTO categorias (nombre) VALUES 
('LED'), ('Ahorrador'), ('Halógeno'), ('Incandescente');

-- Luego productos
INSERT INTO productos (nombre, categoria_id, costo_compra, precio_venta) VALUES
('Foco LED 10W', 1, 8.50, 15.00),
('Foco LED 15W', 1, 12.00, 22.00);categorias