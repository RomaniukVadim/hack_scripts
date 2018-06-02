CREATE DATABASE geoip;
USE geoip;
CREATE TABLE csv (start_ip CHAR(15) NOT NULL, end_ip CHAR(15) NOT NULL, start INT UNSIGNED NOT NULL, end INT UNSIGNED NOT NULL, cc CHAR(2) NOT NULL, cn VARCHAR(50) NOT NULL);
LOAD DATA LOCAL INFILE 'c:/test/geoip.csv' INTO TABLE csv FIELDS TERMINATED BY ',' ENCLOSED BY '"' (start_ip, end_ip, start, end, cc, cn);
