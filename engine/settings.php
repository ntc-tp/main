<?php
//General settings
define('G_MAX_DATA_FILE_SIZE_TO_USER_UPLOAD', '1000000'); //max size of uploading file from Web UI, in bytes
//DB Settings
define('G_DB_HOST', 'localhost'); //dDB host
define('G_DB_NAME', 'sample_db'); //DB name
define('G_DB_USER', 'dbuser'); //DB user
define('G_DB_PASSWORD', 'wer321q!'); //DB password
define('G_DB_TYPE', 'MYSQL'); //DB type (currently supported only MySQL)
define('G_DB_WAREHOUSE_TABLE_NAME', 'sample_tbl'); //DB table, into which CSV data will be loaded
$db_warehouse_table_index_columns = [
    0 => 'Фамилия Имя',
    1 => 'E-mail'
];
define('G_DB_WAREHOUSE_TABLE_INDEX_COLUMNS', $db_warehouse_table_index_columns); //Index of DB table, into which CSV data will be loaded. That index will be used for unique record identifying. Index may be composite and includes many columns. Columns must have string types
define('G_DB_WAREHOUSE_TABLE_PRMARY_KEY_COLUMN','pid'); //Primary Key column of DB table, into which CSV data will be loaded. That key column will be created independent of any other columns. Type always will be Int
//Incoming CSV Data File settings
define('G_CSV_COLUMNS_DELIMETER',';'); //CSV columns delimeter
define('G_CSV_ROWS_DELIMETER',PHP_EOL); //CSV rows delimeter
define('G_HEADER_AT_ROW_NUMBER',1); //number of row where header column is located
