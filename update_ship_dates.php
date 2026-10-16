<?php
    $pdo = require __DIR__ . '/helpers/database.php';

    // Are we running this code in the CLI or the browser?
    if (PHP_SAPI === 'cli') {
        $options = getopt('', ['dryRun:']);
        $dryRunValue = $options['dryRun'] ?? 'true';
    } else {
        $dryRunValue = $_GET['dryRun'] ?? 'true';
    }

    $dryRun = strtolower($dryRunValue) !== 'false';
    echo $dryRun
        ? "DRY RUN: No database changes will be made."
        : "LIVE RUN: Database updates enabled.";

    $orders = $pdo->query(
        'SELECT orderid, comments 
        FROM sweetwater_test 
        WHERE shipdate_expected IS NULL
        ORDER BY orderid'
    )->fetchAll();

    $successful = 0;
    $failed = 0;

    $update = $pdo->prepare(
        'UPDATE sweetwater_test
        SET shipdate_expected = :shipdate, comments = :comments
        WHERE orderid = :orderid'
    );

    foreach ($orders as $order) {

        // Get orderId and comments
        $orderId = $order['orderid'];
        $comments = $order['comments'] ?? '';
        $comments = preg_replace('/\R+/', ' ', $comments) ?? $comments;

        // We will do a regex to find the expected ship date in the comments
        // What if the Expected Ship Date is not in the exact format?
        if (!preg_match(
            '/Expected Ship Date:\s*(\d{2}\/\d{2}\/\d{2})\b/i',
            $comments,
            $matches
        )) {
            //echo "Order {$orderId}: No date found => NULL" . PHP_EOL;
            $failed++;
            continue;
        }

        $expectedDateMatch = $matches[0];
        $dateText = $matches[1];
        $date = DateTimeImmutable::createFromFormat('!m/d/y', $dateText);
        $shipDate = $date->format('Y-m-d H:i:s');

        // We will remove the expected ship date from the comments
        // also trim any extra whitespace left behind
        $comments = trim(str_replace($expectedDateMatch, '', $comments));

        //If this is not a dry run, update the database
        if (!$dryRun) {
            $update->execute([
                'shipdate' => $shipDate,
                'comments' => $comments,
                'orderid' => $orderId,
            ]);
        }

        $successful++;
    }

    echo PHP_EOL;
    echo $dryRun
        ? "Dry run complete. No database changes made." . PHP_EOL
        : "Live run complete. Database updates applied." . PHP_EOL;
    echo "Successful: {$successful}" . PHP_EOL;
    echo "Failed: {$failed}" . PHP_EOL;
?>