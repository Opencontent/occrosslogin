CREATE TABLE  ezoctoken  (
  user_id  integer NOT NULL,
  time  integer NOT NULL,
  session_id  character varying(32) NOT NULL,
  token  character varying(32) NOT NULL
);

ALTER TABLE ONLY ezoctoken ADD CONSTRAINT ezoctoken_pkey PRIMARY KEY (token);