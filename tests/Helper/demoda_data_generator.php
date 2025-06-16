<?php

// phpcs:disable PSR1.Files.SideEffects


declare(strict_types=1);

/**
 * Generates a demo CSV file with evolving daily order counts,
 * output columns: order_nr;order_items;sum;datetime;payment
 */

function linearInterpolate(float $x, array $xs, array $ys): float
{
    $n = count($xs);
    if ($x <= $xs[0]) {
        return $ys[0];
    }
    if ($x >= $xs[$n - 1]) {
        return $ys[$n - 1];
    }
    for ($i = 0; $i < $n - 1; $i++) {
        if ($x >= $xs[$i] && $x <= $xs[$i + 1]) {
            $x0 = $xs[$i];
            $y0 = $ys[$i];
            $x1 = $xs[$i + 1];
            $y1 = $ys[$i + 1];
            return $y0 + ($x - $x0) * ($y1 - $y0) / ($x1 - $x0);
        }
    }
    return $ys[0];
}

function randomFloat(float $min, float $max): float
{
    return $min + lcg_value() * ($max - $min);
}

// Anchor dates and target daily order counts
$anchorData = [
    ['date' => '2024-01-01', 'count' => 2],
    ['date' => '2024-04-20', 'count' => 42],
    ['date' => '2024-07-01', 'count' => 17],
];

// Sort anchor dates
$dates = array_column($anchorData, 'date');
sort($dates);
$startDate = new DateTimeImmutable($dates[0]);
$endDate   = new DateTimeImmutable($dates[count($dates) - 1]);
$totalDays = (int)$startDate->diff($endDate)->format('%a') + 1;

// Build interpolation arrays
$xs = $ys = [];
foreach ($anchorData as $anchor) {
    $d    = new DateTimeImmutable($anchor['date']);
    $idx  = (int)$startDate->diff($d)->format('%a');
    $xs[] = $idx;
    $ys[] = $anchor['count'];
}

// Prepare output CSV
$outputFile = __DIR__ . '/demo_orders.csv';
$fp = fopen($outputFile, 'w');
if ($fp === false) {
    fwrite(STDERR, "Error: Cannot open {$outputFile} for writing\n");
    exit(1);
}

// Write header with semicolon delimiter
fputcsv($fp, ['order_nr','order_items','sum','datetime','payment'], ';');

// Define payment methods
$paymentMethods = ['Credit Card','PayPal','Cash','Bank Transfer'];

$orderCounter = 1;

// Generate data day by day
for ($i = 0; $i < $totalDays; $i++) {
    $current = $startDate->add(new DateInterval("P{$i}D"));

    // Determine number of orders for the day
    $base = linearInterpolate($i, $xs, $ys);
    $noise = randomFloat(-10, 10);
    $count = max(4, (int) floor($base + $noise));

    // Determine revenue trend (mean from 50 → 200)
    $revMean = 50 + ($i / ($totalDays - 1)) * 150;

    for ($j = 0; $j < $count; $j++) {
        // Random time within the day
        $sec = random_int(0, 86399);
        $dt = $current->modify("+{$sec} seconds");

        // Random revenue around the mean
        $sum = round(randomFloat($revMean * 0.8, $revMean * 1.2), 2);

        // Random item count
        $items = random_int(1, 10);

        // Random payment method
        $pm = $paymentMethods[array_rand($paymentMethods)];

        // Write row
        fputcsv($fp, [
            sprintf('ORD-%04d', $orderCounter),
            $items,
            number_format($sum, 2, '.', ''),
            $dt->format('Y-m-d H:i:s'),
            $pm,
        ], ';');

        $orderCounter++;
    }
}

fclose($fp);
echo "Wrote " . ($orderCounter - 1) . " orders to {$outputFile}\n";
