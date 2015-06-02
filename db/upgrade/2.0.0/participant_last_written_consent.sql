DROP PROCEDURE IF EXISTS patch_participant_last_written_consent;
DELIMITER //
CREATE PROCEDURE patch_participant_last_written_consent()
  BEGIN

    SELECT "Adding new participant_last_written_consent caching table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.VIEWS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "participant_last_written_consent" );
    IF @test = 1 THEN

      DROP VIEW IF EXISTS participant_last_written_consent;

      CREATE TABLE IF NOT EXISTS participant_last_written_consent (
        participant_id INT UNSIGNED NOT NULL,
        consent_id INT UNSIGNED NULL,
        update_timestamp TIMESTAMP NOT NULL,
        create_timestamp TIMESTAMP NOT NULL,
        PRIMARY KEY (participant_id),
        INDEX fk_consent_id (consent_id ASC),
        CONSTRAINT fk_participant_last_written_consent_participant_id
          FOREIGN KEY (participant_id)
          REFERENCES participant (id)
          ON DELETE CASCADE
          ON UPDATE CASCADE,
        CONSTRAINT fk_participant_last_written_consent_consent_id
          FOREIGN KEY (consent_id)
          REFERENCES consent (id)
          ON DELETE NO ACTION
          ON UPDATE NO ACTION)
      ENGINE = InnoDB;

      SELECT "Populating participant_last_written_consent table" AS "";

      REPLACE INTO participant_last_written_consent( participant_id, consent_id )
      SELECT participant.id, consent.id
      FROM participant
      LEFT JOIN consent ON participant.id = consent.participant_id
      AND consent.date <=> (
        SELECT MAX( date )
        FROM consent
        WHERE consent.written = true
        AND participant.id = consent.participant_id
        GROUP BY consent.participant_id
        LIMIT 1
      );

    END IF;

  END //
DELIMITER ;

CALL patch_participant_last_written_consent();
DROP PROCEDURE IF EXISTS patch_participant_last_written_consent;
