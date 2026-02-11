-- Run dit éénmalig in phpMyAdmin / MySQL (als kolommen ontbreken)

-- ROOSTERS: extra info voor klus doorsturen + werknemer status
ALTER TABLE roosters
  ADD COLUMN titel VARCHAR(255) NULL,
  ADD COLUMN locatie VARCHAR(255) NULL,
  ADD COLUMN omschrijving TEXT NULL,
  ADD COLUMN toelichting TEXT NULL,
  ADD COLUMN extra_werkzaamheden TEXT NULL,
  ADD COLUMN werknemer_id INT NULL,
  ADD COLUMN opdrachtgever_id INT NULL,
  ADD COLUMN status VARCHAR(50) NULL DEFAULT 'gepland';

-- AFWEZIGHEDEN: reden optioneel
ALTER TABLE afwezigheden
  ADD COLUMN reden VARCHAR(255) NULL;

-- UPLOADS: koppeling aan rooster + user + type
ALTER TABLE uploads
  ADD COLUMN rooster_id INT NULL,
  ADD COLUMN user_id INT NULL,
  ADD COLUMN bestandsnaam VARCHAR(255) NULL,
  ADD COLUMN type VARCHAR(50) NULL DEFAULT 'foto';
