SET @pw := '$2y$10$DH0D6GIGlLeWO6d9TjPqRehPEYfq3PypJzcRRvPvBdvjKWLEDAFva';

INSERT INTO users (naam, email, wachtwoord, rol_id, werkgever_id, telefoonnummer, actief)
VALUES ('Werkgever Test', 'werkgever@test.nl', @pw, 1, NULL, '+31 6 11111111', TRUE)
ON DUPLICATE KEY UPDATE
  naam = VALUES(naam),
  wachtwoord = VALUES(wachtwoord),
  rol_id = VALUES(rol_id),
  werkgever_id = NULL,
  telefoonnummer = VALUES(telefoonnummer),
  actief = TRUE;

INSERT INTO users (naam, email, wachtwoord, rol_id, telefoonnummer, actief)
VALUES ('Super Admin', 'admin@profplanner.local', @pw, 3, '+31 6 00000000', TRUE)
ON DUPLICATE KEY UPDATE
  naam = VALUES(naam),
  wachtwoord = VALUES(wachtwoord),
  rol_id = VALUES(rol_id),
  werkgever_id = NULL,
  telefoonnummer = VALUES(telefoonnummer),
  actief = TRUE;

SET @werkgever_id := (SELECT id FROM users WHERE email = 'werkgever@test.nl' LIMIT 1);

INSERT INTO users (naam, email, wachtwoord, rol_id, werkgever_id, telefoonnummer, actief)
VALUES ('Werknemer Test', 'werknemer@test.nl', @pw, 2, @werkgever_id, '+31 6 22222222', TRUE)
ON DUPLICATE KEY UPDATE
  naam = VALUES(naam),
  wachtwoord = VALUES(wachtwoord),
  rol_id = VALUES(rol_id),
  werkgever_id = VALUES(werkgever_id),
  telefoonnummer = VALUES(telefoonnummer),
  actief = TRUE;

INSERT INTO opdrachtgevers (werkgever_id, naam, email, telefoonnummer, adres)
VALUES (@werkgever_id, 'Demo Opdrachtgever BV', 'planning@demo-opdrachtgever.nl', '+31 20 123 4567', 'Demo Straat 1, Amsterdam')
ON DUPLICATE KEY UPDATE
  werkgever_id = VALUES(werkgever_id),
  email = VALUES(email),
  telefoonnummer = VALUES(telefoonnummer),
  adres = VALUES(adres);

SET @werknemer_id := (SELECT id FROM users WHERE email = 'werknemer@test.nl' LIMIT 1);
SET @opdrachtgever_id := (SELECT id FROM opdrachtgevers WHERE naam = 'Demo Opdrachtgever BV' AND werkgever_id = @werkgever_id LIMIT 1);

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
  'Demo klus isolatie',
  'Demo Locatie 100, Rotterdam',
  'Voorbeeldklus voor snelle validatie van de volledige flow.',
  @werknemer_id, @opdrachtgever_id, @werkgever_id, @bus_id, 'gepland'
WHERE @werknemer_id IS NOT NULL
  AND @werkgever_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM roosters
    WHERE titel = 'Demo klus isolatie'
      AND datum = CURDATE()
      AND werknemer_id = @werknemer_id
  );

INSERT INTO sales_leads (
  werkgever_id, opdrachtgever_id, titel, contact_persoon, contact_email, contact_telefoon, gewenste_datum, notities, status
)
SELECT
  @werkgever_id, @opdrachtgever_id, 'Dakisolatie offerteaanvraag', 'Jan de Vries', 'jan@demo-opdrachtgever.nl', '+31 6 33333333', DATE_ADD(CURDATE(), INTERVAL 3 DAY), 'Nieuwe lead vanuit saleskanaal.', 'nieuw'
WHERE NOT EXISTS (
  SELECT 1 FROM sales_leads
  WHERE titel = 'Dakisolatie offerteaanvraag'
    AND werkgever_id = @werkgever_id
);
