-- @BEGIN MY,PG
BEGIN;
-- @END

--------------------------------------------------------------------------------
-- BASE
--------------------------------------------------------------------------------
-- @BEGIN MY,PG
DROP TABLE IF EXISTS waggo6_example_price;
CREATE TABLE waggo6_example_price (
    id INTEGER NOT NULL,
    name VARCHAR(256),
    price INTEGER NOT NULL
);
ALTER TABLE waggo6_example_price ADD PRIMARY KEY (id);

INSERT INTO waggo6_example_price VALUES(1 ,'Apple',     7102);
INSERT INTO waggo6_example_price VALUES(2 ,'Banana',    5008);
INSERT INTO waggo6_example_price VALUES(3 ,'Carrot',    8755);
INSERT INTO waggo6_example_price VALUES(4 ,'Date',      2002);
INSERT INTO waggo6_example_price VALUES(5 ,'Eggplant',  9580);
INSERT INTO waggo6_example_price VALUES(6 ,'Fig',       8717);
INSERT INTO waggo6_example_price VALUES(7 ,'Grape',     7265);
INSERT INTO waggo6_example_price VALUES(8 ,'Honeydew',  3691);
INSERT INTO waggo6_example_price VALUES(9 ,'IcePlant',  7126);
INSERT INTO waggo6_example_price VALUES(10,'Jellybean', 4523);
-- @END

-- @BEGIN MY,PG
COMMIT;
-- @END
