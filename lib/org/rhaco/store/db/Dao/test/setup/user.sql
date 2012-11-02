CREATE USER 'readonly'@'localhost' IDENTIFIED BY  '***';

GRANT SELECT , 
INDEX , 
CREATE TEMPORARY TABLES ,
SHOW VIEW ,
CREATE ROUTINE,
ALTER ROUTINE,
EXECUTE ON * . * TO  'readonly'@'localhost' IDENTIFIED BY  '***' WITH MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0 ;
