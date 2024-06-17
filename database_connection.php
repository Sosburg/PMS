<?php
$config = parse_ini_file('config.ini', true);

// Local database connection
$local_db_config = $config['local_database'];
$local_conn = new mysqli(
    $local_db_config['host'],
    $local_db_config['username'],
    $local_db_config['password'],
    $local_db_config['dbname']
);

// Home affairs database connection
$home_db_config = $config['home_affairs_database'];
$home_conn = new mysqli(
    $home_db_config['host'],
    $home_db_config['username'],
    $home_db_config['password'],
    $home_db_config['dbname']
);

// Check local database connection
if ($local_conn->connect_error) {
    die("Connection failed: " . $local_conn->connect_error);
}

// Check home affairs database connection
if ($home_conn->connect_error) {
    die("Connection failed: " . $home_conn->connect_error);
}
?>
