SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS rollen (
  id INT AUTO_INCREMENT PRIMARY KEY,
  naam VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  naam VARCHAR(150) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  wachtwoord VARCHAR(255) NOT NULL,
  rol_id INT NOT NULL,
  werkgever_id INT NULL,
  telefoonnummer VARCHAR(30) NULL,
  actief BOOLEAN NOT NULL DEFAULT TRUE,
  gemaakt_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  gewijzigd_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_users_werkgever (werkgever_id),
  INDEX idx_users_rol (rol_id),
  CONSTRAINT fk_users_rol FOREIGN KEY (rol_id) REFERENCES rollen(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_users_werkgever FOREIGN KEY (werkgever_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS opdrachtgevers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  werkgever_id INT NULL,
  naam VARCHAR(190) NOT NULL,
  email VARCHAR(190) NULL,
  telefoonnummer VARCHAR(30) NULL,
  adres VARCHAR(255) NULL,
  gemaakt_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  gewijzigd_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_opdrachtgevers_werkgever (werkgever_id),
  CONSTRAINT fk_opdrachtgevers_werkgever FOREIGN KEY (werkgever_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS buses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  werkgever_id INT NULL,
  naam VARCHAR(50) NOT NULL,
  omschrijving TEXT NULL,
  kleur VARCHAR(10) NOT NULL DEFAULT '#16a34a',
  actief BOOLEAN NOT NULL DEFAULT TRUE,
  gemaakt_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  gewijzigd_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_buses_werkgever_naam (werkgever_id, naam),
  INDEX idx_buses_werkgever (werkgever_id),
  CONSTRAINT fk_buses_werkgever FOREIGN KEY (werkgever_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS roosters (
  id INT AUTO_INCREMENT PRIMARY KEY,
  datum DATE NOT NULL,
  tijd TIME NULL,
  starttijd TIME NULL,
  eindtijd TIME NULL,
  titel VARCHAR(255) NULL,
  locatie VARCHAR(255) NULL,
  omschrijving TEXT NULL,
  toelichting TEXT NULL,
  extra_werkzaamheden TEXT NULL,
  werknemer_id INT NULL,
  opdrachtgever_id INT NULL,
  werkgever_id INT NULL,
  bus_id INT NULL,
  status VARCHAR(50) NOT NULL DEFAULT 'gepland',
  gemaakt_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  gewijzigd_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_roosters_datum (datum),
  INDEX idx_roosters_werknemer (werknemer_id),
  INDEX idx_roosters_status (status),
  INDEX idx_roosters_bus (bus_id),
  INDEX idx_roosters_werkgever (werkgever_id),
  CONSTRAINT fk_roosters_werknemer FOREIGN KEY (werknemer_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_roosters_opdrachtgever FOREIGN KEY (opdrachtgever_id) REFERENCES opdrachtgevers(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_roosters_werkgever FOREIGN KEY (werkgever_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_roosters_bus FOREIGN KEY (bus_id) REFERENCES buses(id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS werknemers_buses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  bus_id INT NOT NULL,
  toegewezen_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_user_bus (user_id, bus_id),
  CONSTRAINT fk_werknemers_buses_user FOREIGN KEY (user_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_werknemers_buses_bus FOREIGN KEY (bus_id) REFERENCES buses(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS afwezigheden (
  id INT AUTO_INCREMENT PRIMARY KEY,
  werknemer_id INT NOT NULL,
  datum DATE NOT NULL,
  reden VARCHAR(255) NULL,
  gemaakt_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_afwezigheden_datum (datum),
  INDEX idx_afwezigheden_werknemer (werknemer_id),
  CONSTRAINT fk_afwezigheden_werknemer FOREIGN KEY (werknemer_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS uploads (
  id INT AUTO_INCREMENT PRIMARY KEY,
  rooster_id INT NOT NULL,
  user_id INT NULL,
  bestandsnaam VARCHAR(255) NOT NULL,
  type VARCHAR(50) NOT NULL DEFAULT 'foto',
  pad VARCHAR(255) NULL,
  gemaakt_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_uploads_rooster (rooster_id),
  INDEX idx_uploads_type (type),
  CONSTRAINT fk_uploads_rooster FOREIGN KEY (rooster_id) REFERENCES roosters(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_uploads_user FOREIGN KEY (user_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sales_leads (
  id INT AUTO_INCREMENT PRIMARY KEY,
  werkgever_id INT NULL,
  opdrachtgever_id INT NULL,
  titel VARCHAR(190) NOT NULL,
  contact_persoon VARCHAR(190) NULL,
  contact_email VARCHAR(190) NULL,
  contact_telefoon VARCHAR(30) NULL,
  gewenste_datum DATE NULL,
  notities TEXT NULL,
  status VARCHAR(40) NOT NULL DEFAULT 'nieuw',
  bevestigd_rooster_id INT NULL,
  gemaakt_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  gewijzigd_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_sales_werkgever (werkgever_id),
  INDEX idx_sales_status (status),
  CONSTRAINT fk_sales_werkgever FOREIGN KEY (werkgever_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_sales_opdrachtgever FOREIGN KEY (opdrachtgever_id) REFERENCES opdrachtgevers(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_sales_rooster FOREIGN KEY (bevestigd_rooster_id) REFERENCES roosters(id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  actor_user_id INT NULL,
  actie VARCHAR(80) NOT NULL,
  doel_type VARCHAR(80) NULL,
  doel_id INT NULL,
  metadata TEXT NULL,
  gemaakt_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_audit_actor (actor_user_id),
  INDEX idx_audit_actie (actie),
  CONSTRAINT fk_audit_actor FOREIGN KEY (actor_user_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO rollen (id, naam) VALUES
  (1, 'werkgever'),
  (2, 'werknemer'),
  (3, 'super_admin')
ON DUPLICATE KEY UPDATE naam = VALUES(naam);
