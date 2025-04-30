-- Crear la base de datos si no existe
CREATE DATABASE IF NOT EXISTS atm;

-- Usar la base de datos
USE atm;

-- Tabla usuario
CREATE TABLE IF NOT EXISTS usuario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pin VARCHAR(4) NOT NULL,
    saldo DECIMAL(10, 2) DEFAULT 1000.00,
    fecha_ingreso DATETIME DEFAULT NULL
);

-- Tabla movimiento
CREATE TABLE IF NOT EXISTS movimiento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT,
    tipo VARCHAR(20) NOT NULL,
    monto DECIMAL(10, 2) NOT NULL,
    fecha_hora DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuario(id)
);

-- Tabla historial_pin
CREATE TABLE IF NOT EXISTS historial_pin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT,
    fecha_hora DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuario(id)
);

-- Insertar datos de prueba (usuario con PIN 1234)
INSERT INTO usuario (pin, saldo) VALUES ('1234', 5000.00);

-- Insertar datos de prueba (usuario con PIN 5678)
INSERT INTO usuario (pin, saldo) VALUES ('5678', 2500.00);

-- Usuario administrador (PIN 9999)
INSERT INTO usuario (pin, saldo) VALUES ('9999', 0.00);


ALTER TABLE historial_pin ADD COLUMN pin_anterior VARCHAR(4) NOT NULL;