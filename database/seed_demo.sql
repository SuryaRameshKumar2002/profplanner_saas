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
