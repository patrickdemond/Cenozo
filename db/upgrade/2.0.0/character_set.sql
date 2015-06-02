DROP PROCEDURE IF EXISTS patch_character_set;
DELIMITER //
CREATE PROCEDURE patch_character_set()
  BEGIN

    -- Declare '_val' variables to read in each record from the cursor
    DECLARE name_val VARCHAR(255);

    -- Declare variables used just for cursor and loop control
    DECLARE no_more_rows BOOLEAN;
    DECLARE loop_cntr INT DEFAULT 0;
    DECLARE num_rows INT DEFAULT 0;

    -- Declare the cursor
    DECLARE the_cursor CURSOR FOR
    SELECT TABLE_NAME
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
    AND IFNULL( TABLE_COLLATION, "utf8_general_ci" ) != "utf8_general_ci";

    -- Declare 'handlers' for exceptions
    DECLARE CONTINUE HANDLER FOR NOT FOUND
    SET no_more_rows = TRUE;

    -- 'open' the cursor and capture the number of rows returned
    -- (the 'select' gets invoked when the cursor is 'opened')
    OPEN the_cursor;
    select FOUND_ROWS() into num_rows;

    the_loop: LOOP

      FETCH  the_cursor
      INTO   name_val;

      -- break out of the loop if
        -- 1) there were no records, or
        -- 2) we've processed them all
      IF no_more_rows THEN
          CLOSE the_cursor;
          LEAVE the_loop;
      END IF;

      -- the equivalent of a 'print statement' in a stored procedure
      -- it simply displays output for each loop
      SELECT CONCAT( "Converting character set to UTF8 for table ", name_val ) AS "";

      SET @sql = CONCAT(
        "ALTER TABLE ", name_val, " ",
        "CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      -- count the number of times looped
      SET loop_cntr = loop_cntr + 1;

    END LOOP the_loop;

    -- now convert the database to utf8
    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.SCHEMATA
      WHERE SCHEMA_NAME = DATABASE()
      AND ( DEFAULT_CHARACTER_SET_NAME != "utf8" OR DEFAULT_COLLATION_NAME != "utf8_general_ci" ) );
    IF @test = 1 THEN
      SELECT CONCAT(
        "*** ATTENTION ***  The database is not set to use the UTF8 character set by default.  ",
        "Please run the following statement: ",
        "\"",
        "ALTER DATABASE ", DATABASE(), " CHARACTER SET utf8 COLLATE utf8_general_ci",
        "\"" ) AS "";
    END IF;

  END //
DELIMITER ;

CALL patch_character_set();
DROP PROCEDURE IF EXISTS patch_character_set;
