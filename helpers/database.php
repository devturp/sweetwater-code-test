<?php
    $configPath = __DIR__ . '/../.env';

    if (!is_file($configPath)) {
        throw new RuntimeException('Database configuration is missing.');
    }

    $dbConfig = parse_ini_file($configPath, false, INI_SCANNER_RAW);

    if ($dbConfig === false) {
        throw new RuntimeException('Unable to read database configuration.');
    }

    return new PDO(
        "mysql:host={$dbConfig['DB_HOST']};dbname={$dbConfig['DB_NAME']};charset=utf8mb4",
        $dbConfig['DB_USER'],
        $dbConfig['DB_PASSWORD'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
?>