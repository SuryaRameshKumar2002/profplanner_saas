-- ProfPlanner Hostinger one-shot install
-- Import this single file in phpMyAdmin

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
  image_path VARCHAR(255) NULL,
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
  sales_user_id INT NULL,
  opdrachtgever_id INT NULL,
  gemeente VARCHAR(120) NOT NULL,
  straatnaam VARCHAR(190) NOT NULL,
  huisnummer VARCHAR(30) NOT NULL,
  voornaam VARCHAR(120) NOT NULL,
  achternaam VARCHAR(120) NOT NULL,
  telefoonnummer VARCHAR(30) NULL,
  email VARCHAR(190) NULL,
  bereikbaar_via VARCHAR(80) NULL,
  afspraak_datum DATETIME NULL,
  adviesgesprek_gepland BOOLEAN NOT NULL DEFAULT FALSE,
  titel VARCHAR(190) NOT NULL DEFAULT 'Nieuwe lead',
  notities TEXT NULL,
  status VARCHAR(40) NOT NULL DEFAULT 'nieuw',
  bevestigd_rooster_id INT NULL,
  gemaakt_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  gewijzigd_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_sales_werkgever (werkgever_id),
  INDEX idx_sales_user (sales_user_id),
  INDEX idx_sales_status (status),
  CONSTRAINT fk_sales_werkgever FOREIGN KEY (werkgever_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_sales_user FOREIGN KEY (sales_user_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_sales_opdrachtgever FOREIGN KEY (opdrachtgever_id) REFERENCES opdrachtgevers(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_sales_rooster FOREIGN KEY (bevestigd_rooster_id) REFERENCES roosters(id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sales_appointments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  werkgever_id INT NULL,
  sales_user_id INT NULL,
  lead_id INT NULL,
  gemeente VARCHAR(120) NOT NULL,
  straatnaam VARCHAR(190) NOT NULL,
  huisnummer VARCHAR(30) NOT NULL,
  klant_achternaam VARCHAR(120) NOT NULL,
  email VARCHAR(190) NULL,
  telefoonnummer VARCHAR(30) NULL,
  afspraak_datum DATETIME NOT NULL,
  bijzonderheden TEXT NULL,
  status VARCHAR(40) NOT NULL DEFAULT 'gepland',
  gemaakt_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  gewijzigd_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_sales_appt_werkgever (werkgever_id),
  INDEX idx_sales_appt_user (sales_user_id),
  INDEX idx_sales_appt_date (afspraak_datum),
  CONSTRAINT fk_sales_appt_werkgever FOREIGN KEY (werkgever_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_sales_appt_user FOREIGN KEY (sales_user_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_sales_appt_lead FOREIGN KEY (lead_id) REFERENCES sales_leads(id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sales_planning_visits (
  id INT AUTO_INCREMENT PRIMARY KEY,
  werkgever_id INT NULL,
  sales_user_id INT NULL,
  lead_id INT NULL,
  gemeente VARCHAR(120) NOT NULL,
  straatnaam VARCHAR(190) NOT NULL,
  huisnummer VARCHAR(30) NOT NULL,
  status VARCHAR(50) NOT NULL DEFAULT 'GEPLAND',
  gepland_op DATE NULL,
  bezocht_op DATETIME NULL,
  notities TEXT NULL,
  gemaakt_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  gewijzigd_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_sales_plan_werkgever (werkgever_id),
  INDEX idx_sales_plan_user (sales_user_id),
  INDEX idx_sales_plan_gemeente (gemeente),
  INDEX idx_sales_plan_status (status),
  CONSTRAINT fk_sales_plan_werkgever FOREIGN KEY (werkgever_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_sales_plan_user FOREIGN KEY (sales_user_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_sales_plan_lead FOREIGN KEY (lead_id) REFERENCES sales_leads(id)
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

CREATE TABLE IF NOT EXISTS notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sender_user_id INT NULL,
  recipient_user_id INT NOT NULL,
  type VARCHAR(50) NOT NULL DEFAULT 'general',
  title VARCHAR(190) NOT NULL,
  message TEXT NULL,
  link VARCHAR(255) NULL,
  gelezen_op DATETIME NULL,
  gemaakt_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_notifications_recipient (recipient_user_id),
  INDEX idx_notifications_read (gelezen_op),
  CONSTRAINT fk_notifications_sender FOREIGN KEY (sender_user_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_notifications_recipient FOREIGN KEY (recipient_user_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL,
  ip VARCHAR(45) NOT NULL,
  success BOOLEAN NOT NULL DEFAULT FALSE,
  attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_login_attempts_email_ip_time (email, ip, attempted_at),
  INDEX idx_login_attempts_time (attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO rollen (id, naam) VALUES
  (1, 'werkgever'),
  (2, 'werknemer'),
  (3, 'super_admin'),
  (4, 'sales_manager'),
  (5, 'sales_agent')
ON DUPLICATE KEY UPDATE naam = VALUES(naam);


-- Seeded accounts and baseline data
INSERT INTO users (naam, email, wachtwoord, rol_id, werkgever_id, telefoonnummer, actief)
VALUES ('Employer Account', 'werkgever@profplanner.app', '$2y$10$4yURdU6PVtK.cMM68iessec6Zx94IyMq1xo2Pp.gxeR/VCKQiArVe', 1, NULL, '+31 6 11111111', TRUE)
ON DUPLICATE KEY UPDATE
  naam = VALUES(naam),
  wachtwoord = VALUES(wachtwoord),
  rol_id = VALUES(rol_id),
  werkgever_id = NULL,
  telefoonnummer = VALUES(telefoonnummer),
  actief = TRUE;

INSERT INTO users (naam, email, wachtwoord, rol_id, telefoonnummer, actief)
VALUES ('Super Admin', 'superadmin@profplanner.app', '$2y$10$L5BJY/i1m9qvN5siV/xQ2uwMCZzqmjoLCqZ0qbhTHOjjHnvoS/Uve', 3, '+31 6 00000000', TRUE)
ON DUPLICATE KEY UPDATE
  naam = VALUES(naam),
  wachtwoord = VALUES(wachtwoord),
  rol_id = VALUES(rol_id),
  werkgever_id = NULL,
  telefoonnummer = VALUES(telefoonnummer),
  actief = TRUE;

SET @werkgever_id := (SELECT id FROM users WHERE email = 'werkgever@profplanner.app' LIMIT 1);

INSERT INTO users (naam, email, wachtwoord, rol_id, werkgever_id, telefoonnummer, actief)
VALUES ('Employee Account', 'werknemer@profplanner.app', '$2y$10$ewmiZL94R4MEO4veF2IrcuhEv05eOgCFHEBiPep6DwjGTzPWm3OW.', 2, @werkgever_id, '+31 6 22222222', TRUE)
ON DUPLICATE KEY UPDATE
  naam = VALUES(naam),
  wachtwoord = VALUES(wachtwoord),
  rol_id = VALUES(rol_id),
  werkgever_id = VALUES(werkgever_id),
  telefoonnummer = VALUES(telefoonnummer),
  actief = TRUE;

INSERT INTO users (naam, email, wachtwoord, rol_id, werkgever_id, telefoonnummer, actief)
VALUES ('Sales Manager', 'salesmanager@profplanner.app', '$2y$10$lYAyXrTtmT77orytkOBqhemFBRQ3iT1NLo62hRpw.MGktBJawlyj6', 4, @werkgever_id, '+31 6 44444444', TRUE)
ON DUPLICATE KEY UPDATE
  naam = VALUES(naam),
  wachtwoord = VALUES(wachtwoord),
  rol_id = VALUES(rol_id),
  werkgever_id = VALUES(werkgever_id),
  telefoonnummer = VALUES(telefoonnummer),
  actief = TRUE;

INSERT INTO users (naam, email, wachtwoord, rol_id, werkgever_id, telefoonnummer, actief)
VALUES ('Sales Agent', 'salesagent@profplanner.app', '$2y$10$oT/wTMPoMYx4Cc80WqGIuum38nlJwR4UuqHtEIzEYrRaXsSb5HYQe', 5, @werkgever_id, '+31 6 55555555', TRUE)
ON DUPLICATE KEY UPDATE
  naam = VALUES(naam),
  wachtwoord = VALUES(wachtwoord),
  rol_id = VALUES(rol_id),
  werkgever_id = VALUES(werkgever_id),
  telefoonnummer = VALUES(telefoonnummer),
  actief = TRUE;

INSERT INTO opdrachtgevers (werkgever_id, naam, email, telefoonnummer, adres)
VALUES (@werkgever_id, 'Northfield Insulation BV', 'planning@northfield-insulation.nl', '+31 20 123 4567', 'Marktstraat 1, Amsterdam')
ON DUPLICATE KEY UPDATE
  werkgever_id = VALUES(werkgever_id),
  email = VALUES(email),
  telefoonnummer = VALUES(telefoonnummer),
  adres = VALUES(adres);

SET @werknemer_id := (SELECT id FROM users WHERE email = 'werknemer@profplanner.app' LIMIT 1);
SET @opdrachtgever_id := (SELECT id FROM opdrachtgevers WHERE naam = 'Northfield Insulation BV' AND werkgever_id = @werkgever_id LIMIT 1);

INSERT INTO buses (werkgever_id, naam, omschrijving, kleur)
VALUES (@werkgever_id, 'HV01', 'Hoog Voltage Team 1', '#16a34a')
ON DUPLICATE KEY UPDATE
  omschrijving = VALUES(omschrijving),
  kleur = VALUES(kleur);

INSERT INTO buses (werkgever_id, naam, omschrijving, kleur)
VALUES (@werkgever_id, 'HV02', 'Hoog Voltage Team 2', '#059669')
ON DUPLICATE KEY UPDATE
  omschrijving = VALUES(omschrijving),
  kleur = VALUES(kleur);

INSERT INTO buses (werkgever_id, naam, omschrijving, kleur)
VALUES (@werkgever_id, 'DVI', 'DVI Specialist Team', '#047857')
ON DUPLICATE KEY UPDATE
  omschrijving = VALUES(omschrijving),
  kleur = VALUES(kleur);

SET @bus_id := (SELECT id FROM buses WHERE naam = 'HV01' AND werkgever_id = @werkgever_id LIMIT 1);

INSERT IGNORE INTO werknemers_buses (user_id, bus_id)
VALUES (@werknemer_id, @bus_id);

INSERT INTO roosters (
  datum, tijd, starttijd, eindtijd, titel, locatie, omschrijving,
  werknemer_id, opdrachtgever_id, werkgever_id, bus_id, status
)
SELECT
  CURDATE(), '08:00:00', '08:00:00', '16:00:00',
  'Isolatie opdracht',
  'Locatie 100, Rotterdam',
  'Voorbeeldklus voor snelle validatie van de volledige flow.',
  @werknemer_id, @opdrachtgever_id, @werkgever_id, @bus_id, 'gepland'
WHERE @werknemer_id IS NOT NULL
  AND @werkgever_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM roosters
    WHERE titel = 'Isolatie opdracht'
      AND datum = CURDATE()
      AND werknemer_id = @werknemer_id
  );

SET @sales_manager_id := (SELECT id FROM users WHERE email = 'salesmanager@profplanner.app' LIMIT 1);

INSERT INTO sales_leads (
  werkgever_id, sales_user_id, opdrachtgever_id, gemeente, straatnaam, huisnummer, voornaam, achternaam, telefoonnummer, email, bereikbaar_via, afspraak_datum, adviesgesprek_gepland, titel, notities, status
)
SELECT
  @werkgever_id, @sales_manager_id, @opdrachtgever_id, 'Amsterdam', 'Keizersgracht', '100', 'Jan', 'de Vries', '+31 6 33333333', 'jan@northfield-insulation.nl', 'telefoon', DATE_ADD(NOW(), INTERVAL 2 DAY), TRUE, 'Dakisolatie offerteaanvraag', 'Nieuwe lead vanuit saleskanaal.', 'nieuw'
WHERE NOT EXISTS (
  SELECT 1 FROM sales_leads
  WHERE titel = 'Dakisolatie offerteaanvraag'
    AND werkgever_id = @werkgever_id
);

SET @lead_id := (SELECT id FROM sales_leads WHERE werkgever_id = @werkgever_id AND titel = 'Dakisolatie offerteaanvraag' LIMIT 1);

INSERT INTO sales_appointments (
  werkgever_id, sales_user_id, lead_id, gemeente, straatnaam, huisnummer, klant_achternaam, email, telefoonnummer, afspraak_datum, bijzonderheden, status
)
SELECT
  @werkgever_id, @sales_manager_id, @lead_id, 'Amsterdam', 'Keizersgracht', '100', 'de Vries', 'jan@northfield-insulation.nl', '+31 6 33333333', DATE_ADD(NOW(), INTERVAL 2 DAY), 'Adviesgesprek isolatie', 'gepland'
WHERE @lead_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM sales_appointments WHERE lead_id = @lead_id
  );

INSERT INTO sales_planning_visits (
  werkgever_id, sales_user_id, lead_id, gemeente, straatnaam, huisnummer, status, gepland_op, notities
)
SELECT
  @werkgever_id, @sales_manager_id, @lead_id, 'Amsterdam', 'Keizersgracht', '100', 'BEZOCHT INTERESSE', CURDATE(), 'Klant toont interesse in pakket A.'
WHERE @lead_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM sales_planning_visits WHERE lead_id = @lead_id
  );

