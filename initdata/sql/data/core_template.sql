-- @BEGIN MY,PG
BEGIN;
-- @END

--------------------------------------------------------------------------------
-- SEQUENCE
--------------------------------------------------------------------------------
-- @BEGIN MY
DROP TABLE IF EXISTS sequence;
CREATE TABLE sequence (
    name VARCHAR(50) NOT NULL,
    current_value INT NOT NULL,
    increment INT NOT NULL DEFAULT 1,
    PRIMARY KEY (name)
);

DROP FUNCTION IF EXISTS currval;
DELIMITER $
CREATE FUNCTION currval (seq_name VARCHAR(50))
    RETURNS INTEGER
    LANGUAGE SQL
    DETERMINISTIC
    CONTAINS SQL
    SQL SECURITY DEFINER
    COMMENT ''
BEGIN
    DECLARE value INTEGER;
    SET value = 0;
    SELECT current_value INTO value
        FROM sequence
        WHERE name = seq_name;
    RETURN value;
END
$
DELIMITER ;

DROP FUNCTION IF EXISTS nextval;
DELIMITER $
CREATE FUNCTION nextval (seq_name VARCHAR(50))
    RETURNS INTEGER
    LANGUAGE SQL
    DETERMINISTIC
    CONTAINS SQL
    SQL SECURITY DEFINER
    COMMENT ''
BEGIN
    UPDATE sequence
    SET current_value = current_value + increment
    WHERE name = seq_name;
    RETURN currval(seq_name);
END
$
DELIMITER ;

DROP FUNCTION IF EXISTS setval;
DELIMITER $
CREATE FUNCTION setval (seq_name VARCHAR(50), value INTEGER)
    RETURNS INTEGER
    LANGUAGE SQL
    DETERMINISTIC
    CONTAINS SQL
    SQL SECURITY DEFINER
    COMMENT ''
BEGIN
    UPDATE sequence
    SET current_value = value
    WHERE name = seq_name;
    RETURN currval(seq_name);
END
$
DELIMITER ;

INSERT INTO sequence VALUES('seq_id',     1000000,1);
INSERT INTO sequence VALUES('seq_usercd', 2000000,1);
INSERT INTO sequence VALUES('seq_grpcd',  5000000,1);
INSERT INTO sequence VALUES('seq_serial', 1000000,1);
-- @END
-- @BEGIN PG
CREATE SEQUENCE seq_id     START 1000000;
CREATE SEQUENCE seq_usercd START 2000000;
CREATE SEQUENCE seq_grpcd  START 5000000;
CREATE SEQUENCE seq_serial START 1000000;
-- @END

--------------------------------------------------------------------------------
-- CONFIG
--------------------------------------------------------------------------------
-- @BEGIN MY,PG
CREATE TABLE config (
    "key" VARCHAR(256) NOT NULL,
    data VARCHAR(256)
);
ALTER TABLE config ADD PRIMARY KEY ("key");
-- @END

--------------------------------------------------------------------------------
-- BASE
--------------------------------------------------------------------------------
-- @BEGIN MY,PG
CREATE TABLE base (
    usercd INTEGER NOT NULL,
    login VARCHAR(256) NOT NULL,
    password VARCHAR(256) NOT NULL,
    name VARCHAR(256) NOT NULL,
    enabled BOOLEAN NOT NULL,
    deny BOOLEAN NOT NULL,
    security INTEGER NOT NULL,
    initymd TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updymd TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
ALTER TABLE base ADD PRIMARY KEY (usercd);
CREATE VIEW base_normal AS SELECT * FROM base WHERE enabled=true AND deny=false;
CREATE UNIQUE INDEX base_pkey1 ON base (login);
INSERT INTO base(usercd,login,password,name,enabled,deny,security,initymd,updymd) VALUES(0,'','','Guest',true,false,0,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP);
-- @END

--------------------------------------------------------------------------------
-- GRPBASE
--------------------------------------------------------------------------------
-- @BEGIN MY,PG
CREATE TABLE grpbase (
    grpcd INTEGER NOT NULL,
    grptype INTEGER NOT NULL,
    enabled BOOLEAN NOT NULL,
    deny BOOLEAN NOT NULL,
    security INTEGER NOT NULL,
    initymd TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updymd TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
ALTER TABLE grpbase ADD PRIMARY KEY (grpcd);
CREATE VIEW grpbase_normal AS SELECT * FROM grpbase WHERE enabled=true AND deny=false;

CREATE TABLE grpmember (
     grpcd INTEGER NOT NULL,
     usercd INTEGER NOT NULL,
     enabled BOOLEAN NOT NULL,
     deny BOOLEAN NOT NULL
);
ALTER TABLE grpmember ADD PRIMARY KEY (grpcd,usercd);
-- @END

--------------------------------------------------------------------------------
-- OWNER
--------------------------------------------------------------------------------
-- @BEGIN MY,PG
CREATE TABLE owner (
    id INTEGER NOT NULL,
    usercd INTEGER NOT NULL,
    enabled BOOLEAN NOT NULL,
    initymd TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updymd TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
ALTER TABLE owner ADD PRIMARY KEY (id);
CREATE INDEX owner_key1 ON owner USING BTREE (usercd);
-- @END

--------------------------------------------------------------------------------
-- PERMISSION
--------------------------------------------------------------------------------
-- @BEGIN MY,PG
CREATE TABLE pmt (
    id INTEGER NOT NULL,
    grpcd INTEGER NOT NULL,
    acl INTEGER NOT NULL
);
ALTER TABLE pmt ADD PRIMARY KEY (id,grpcd);
-- @END

--------------------------------------------------------------------------------
-- UNIQUE CONFIRMING INFORMATION
--------------------------------------------------------------------------------
-- @BEGIN MY,PG
CREATE TABLE uniqconfirm (
    uniqid VARCHAR(256) NOT NULL,
    serviceid VARCHAR(256) NOT NULL,
    usercd INTEGER NOT NULL,
    grpcd INTEGER NOT NULL,
    data TEXT NOT NULL,
    enabled BOOLEAN NOT NULL,
    deny BOOLEAN NOT NULL,
    expired TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    initymd TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updymd TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
ALTER TABLE uniqconfirm ADD PRIMARY KEY (uniqid);
CREATE INDEX uniqconfirm_key1 ON uniqconfirm USING BTREE (serviceid,usercd,grpcd);
CREATE INDEX uniqconfirm_key2 ON uniqconfirm USING BTREE (expired);
-- @END

--------------------------------------------------------------------------------
-- RESOURCE
--------------------------------------------------------------------------------
-- @BEGIN MY,PG
CREATE TABLE resource (
    id INTEGER NOT NULL,
    pid INTEGER NOT NULL,
    type INTEGER NOT NULL,
    mime VARCHAR(256) NOT NULL,
    filename VARCHAR(256) NOT NULL,
    ext VARCHAR(256) NOT NULL,
    title VARCHAR(256),
    comment text,
    initymd TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updymd TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deny BOOLEAN DEFAULT false
);
ALTER TABLE resource ADD PRIMARY KEY (id);
CREATE INDEX resource_key1 ON resource USING btree (pid);
CREATE UNIQUE INDEX resource_key2 ON resource USING btree (filename);
-- @END

--------------------------------------------------------------------------------
-- RESOURCE STATUS
--------------------------------------------------------------------------------
-- @BEGIN MY,PG
CREATE TABLE resourcestatus (
    id INTEGER NOT NULL,
    type INTEGER NOT NULL,
    status VARCHAR(256) NOT NULL,
    initymd TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updymd TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
ALTER TABLE resourcestatus ADD PRIMARY KEY (id);
-- @END

--------------------------------------------------------------------------------
-- TAG
--------------------------------------------------------------------------------
-- @BEGIN MY,PG
CREATE TABLE tag (
    id INTEGER NOT NULL,
    name VARCHAR(256) NOT NULL
);
ALTER TABLE tag ADD PRIMARY KEY (id);
-- @END

--------------------------------------------------------------------------------
-- TAG RELATION
--------------------------------------------------------------------------------
-- @BEGIN MY,PG
CREATE TABLE tagrelation (
    id INTEGER NOT NULL,
    tagid INTEGER NOT NULL
);
ALTER TABLE tagrelation ADD PRIMARY KEY (id,tagid);
-- @END

-- @BEGIN MY,PG
COMMIT;
-- @END
