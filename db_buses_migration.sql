-- ProfPlanner Buses Module Migration
-- Add buses table and connect to roosters for weekly team-based planning

-- Create BUSES table (teams/squads: HV01, HV02, DVI, etc.)
CREATE TABLE IF NOT EXISTS buses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  naam VARCHAR(50) UNIQUE NOT NULL,
  omschrijving TEXT NULL,
  kleur VARCHAR(10) DEFAULT '#16a34a',
  actief BOOLEAN DEFAULT TRUE,
  gemaakt_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  gewijzigd_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add bus_id to roosters table if not exists
ALTER TABLE roosters
  ADD COLUMN bus_id INT NULL,
  ADD CONSTRAINT fk_roosters_bus FOREIGN KEY (bus_id) REFERENCES buses(id) ON DELETE SET NULL;

-- Create WERKNEMERS_BUSES junction table for many-to-many relationship
CREATE TABLE IF NOT EXISTS werknemers_buses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  bus_id INT NOT NULL,
  toegewezen_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_user_bus (user_id, bus_id),
  CONSTRAINT fk_werknemers_buses_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_werknemers_buses_bus FOREIGN KEY (bus_id) REFERENCES buses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default buses
INSERT INTO buses (naam, omschrijving, kleur) VALUES
('HV01', 'Hoog Voltage Team 1', '#16a34a'),
('HV02', 'Hoog Voltage Team 2', '#059669'),
('DVI', 'DVI Specialist Team', '#047857')
ON DUPLICATE KEY UPDATE omschrijving = VALUES(omschrijving);
