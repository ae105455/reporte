CREATE DATABASE IF NOT EXISTS aureamed;
USE aureamed;

CREATE TABLE usuarios (
  id_usuario INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  usuario VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  rol VARCHAR(30) NOT NULL DEFAULT 'admin',
  estado VARCHAR(20) NOT NULL DEFAULT 'activo'
);

CREATE TABLE pacientes (
  id_paciente INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  apellido VARCHAR(100) NOT NULL,
  telefono VARCHAR(20),
  direccion VARCHAR(200)
);

CREATE TABLE servicios (
  id_servicio INT AUTO_INCREMENT PRIMARY KEY,
  nombre_servicio VARCHAR(150) NOT NULL,
  precio DECIMAL(10,2) NOT NULL,
  duracion_minutos INT NOT NULL DEFAULT 30,
  estado VARCHAR(20) NOT NULL DEFAULT 'activo'
);

CREATE TABLE reservas (
  id_reserva INT AUTO_INCREMENT PRIMARY KEY,
  id_paciente INT NOT NULL,
  id_servicio INT NOT NULL,
  fecha_reserva DATE NOT NULL,
  hora_reserva TIME NOT NULL,
  estado VARCHAR(30) NOT NULL DEFAULT 'pendiente',
  FOREIGN KEY (id_paciente) REFERENCES pacientes(id_paciente),
  FOREIGN KEY (id_servicio) REFERENCES servicios(id_servicio)
);

INSERT INTO usuarios (nombre, usuario, password, rol, estado) VALUES
('Admin', 'admin', MD5('123456'), 'admin', 'activo');

INSERT INTO pacientes (nombre, apellido, telefono, direccion) VALUES
('Ana', 'Pérez', '77712345', 'La Paz'),
('Luis', 'Mamani', '77745678', 'El Alto'),
('Carmen', 'Flores', '77798765', 'Santa Cruz');

INSERT INTO servicios (nombre_servicio, precio, duracion_minutos, estado) VALUES
('Consulta General', 250.00, 30, 'activo'),
('Audiometría', 400.00, 45, 'activo'),
('Control de Oído', 300.00, 40, 'activo'),
('Revisión', 200.00, 25, 'activo');

INSERT INTO reservas (id_paciente, id_servicio, fecha_reserva, hora_reserva, estado) VALUES
(1, 1, '2026-08-10', '09:00:00', 'confirmada'),
(2, 2, '2026-08-11', '10:30:00', 'confirmada'),
(1, 3, '2026-08-12', '11:00:00', 'pendiente'),
(3, 1, '2026-08-13', '15:00:00', 'atendida'),
(2, 4, '2026-08-14', '16:30:00', 'cancelada');

SELECT * FROM usuarios;
SELECT * FROM pacientes;
SELECT * FROM servicios;
SELECT * FROM reservas;
