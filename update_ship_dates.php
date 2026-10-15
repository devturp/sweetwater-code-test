<?php

    $pdo = new PDO(
        'mysql:host=localhost;dbname=sweetwater;charset=utf8mb4',
        'root',
        'password',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    $orders = $pdo->query(
        'SELECT orderid, comments FROM sweetwater_test ORDER BY orderid'
    )->fetchAll();

    $successful = 0;
    $failed = 0;

    foreach ($orders as $order) {

        // Get orderId and comments
        $orderId = $order['orderid'];
        $comments = $order['comments'] ?? '';

        // We will do a regex to find the expected ship date in the comments
        // What if the Expected Ship Date is not in the exact format?
        if (!preg_match(
            '/Expected Ship Date:\s*(\d{2}\/\d{2}\/\d{2})\b/i',
            $order['comments'] ?? '',
            $matches
        )) {
            echo "Order {$orderId}: No date found => NULL" . PHP_EOL;
            $failed++;
            continue;
        }
        
        $dateText = $matches[1];
        $date = DateTimeImmutable::createFromFormat('!m/d/y', $dateText);
        $errors = DateTimeImmutable::getLastErrors();

        if (
            $date === false ||
            ($errors !== false &&
                ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) ||
            $date->format('m/d/y') !== $dateText
        ) {
            echo "Order {$orderId}: FAILED, invalid date '{$dateText}'." . PHP_EOL;
            $failed++;
            continue;
        }

        echo "Order {$orderId}: {$dateText} => "
            . $date->format('Y-m-d H:i:s')
            . PHP_EOL;

        $successful++;
    }

    echo PHP_EOL;
    echo "Dry run complete. No database changes made." . PHP_EOL;
    echo "Successful: {$successful}" . PHP_EOL;
    echo "Failed: {$failed}" . PHP_EOL;

?>