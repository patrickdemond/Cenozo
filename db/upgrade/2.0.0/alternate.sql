DROP PROCEDURE IF EXISTS patch_alternate;
DELIMITER //
CREATE PROCEDURE patch_alternate()
  BEGIN

    SELECT "Dropping person_id column from alternate table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "alternate"
      AND COLUMN_NAME = "person_id" );
    IF @test = 1 THEN
      SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
      SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;

      -- drop column
      ALTER TABLE alternate
      DROP FOREIGN KEY fk_alternate_person,
      DROP INDEX fk_person_id,
      DROP INDEX uq_person_id,
      DROP COLUMN person_id;

      SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
      SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
    END IF;

  END //
DELIMITER ;

CALL patch_alternate();
DROP PROCEDURE IF EXISTS patch_alternate;
