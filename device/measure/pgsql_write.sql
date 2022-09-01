CREATE TABLE __TABLE_NAME__ (
  dta_idx SERIAL,
  dta_dt TIMESTAMPTZ NOT NULL,
  dta_type integer NOT NULL,
  dta_no integer NOT NULL,
  dta_value DOUBLE PRECISION NULL,
  dta_1 integer default 0,
  dta_2 integer default 0,
  dta_3 integer default 0
);