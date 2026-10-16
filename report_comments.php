<?php
    $pdo = require __DIR__ . '/helpers/database.php';

    $rules = [
        'Candy Comments' => '/\b(candy|candies|sweet\s*tarts?|smarties|tootsie|suckers?|lollipops?)\b/i',
        "Call Comments" => '/\b(call me|dont call|call|telephone)\b/i',
        'Referral Comments' => '/\b(referr?\w*|recommended|recommendation|sales rep|sales engineer|person)\b/i',
        'Signature Comments' => '/\b(signatures?|sign(?:ed|ing)?\s+for|sign|sign\s+on\s+delivery)\b/i',
    ];

    // "Candy Comments", "Call Comments", "Referral Comments", "Signature Comments"
    $ruleArrayKeys = array_keys($rules);
    $commentSections = array_fill_keys($ruleArrayKeys, []);
    $commentSections['Miscellaneous Comments'] = [];

    // This query gets all orders where comments are not null.
    $orders = $pdo->query(
        'SELECT orderid, comments, shipdate_expected
        FROM sweetwater_test
        WHERE comments IS NOT NULL
        ORDER BY orderid'
    );

    // Loop through each order, then put them into different sections.
    foreach ($orders as $order) {
        $isMatched = false;
        // For each rule, we check if the comment matches the pattern
        foreach ($rules as $section => $regexPattern) {
            // If comment matches the pattern
            if (preg_match($regexPattern, $order['comments']) === 1) {
                $commentSections[$section][] = $order;
                $isMatched = true;
            }
        }
        // If no rules matched at all, we put them into the misc category
        if (!$isMatched) {
            $commentSections['Miscellaneous Comments'][] = $order;
        }
    }

    function sanitizeString(string $value): string {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <title>SweetComments | Report Comments</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                max-width: 1100px;
                margin: 32px auto;
                padding: 0 16px;
                color: #222;
            }

            div.commentSection {
                margin-bottom: 36px;
            }

            table {
                width: 100%;
                border-collapse: collapse;
            }

            th, td {
                padding: 12px;
                border: 1px solid #ddd;
                text-align: left;
                vertical-align: top;
            }

            th {
                background: #f3f4f6;
            }

            .comment {
                white-space: pre-wrap;
                overflow-wrap: anywhere;
            }

            .callout {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 16px;
                margin: 24px 0;
                padding: 16px 20px;
                background: #e4f4ec;
                border-left: 10px solid #21845b;
            }

            .callout p {
                margin: 0;
            }
        </style>
    </head>
    <body>
        <div class="callout">
            <p>
                <strong>Note:</strong> For the most accurate "Expected Ship Dates", please ensure the internal update script is ran regularly to keep the data in compliance. Contact an Admin for assistance.</p>
        </div>

        <img src="images/sweet-comments.png" style="max-width: 100%; height: auto;" alt="SweetComments Logo">
        <p>
            Comments can appear in more than one section if they match multiple patterns.
            Each heading shows the number of matching orders.
        </p>

        <!-- Anchor links to each section -->
        <ul>
            <?php foreach ($commentSections as $section => $orders): ?>
                <li><a href="#<?= str_replace(' ', '-', strtolower(sanitizeString($section))) ?>"><?= sanitizeString($section) ?></a></li>
            <?php endforeach; ?>
        </ul>

        <?php foreach ($commentSections as $section => $orders): ?>
            <div class="commentSection" id="<?= str_replace(' ', '-', strtolower(sanitizeString($section))) ?>">
                <h2>
                    <?= sanitizeString($section) ?>
                    (<?= count($orders) ?>)
                </h2>

                <?php if (!$orders): ?>
                    <p>No matching comments.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Comment</th>
                                <th>Expected Ship Date</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?= sanitizeString($order['orderid']) ?></td>
                                <td class="comment"><?= sanitizeString($order['comments']) ?></td>
                                <td>
                                    <?php
                                        if ($order['shipdate_expected']) {
                                            $date = new DateTime($order['shipdate_expected']);
                                            $shipDate = $date->format('Y-m-d');
                                            echo $shipDate;
                                        }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </body>
</html>