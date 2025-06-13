# Demo Orders CSV Generator
This repository provides a PHP script to generate sample “orders” data and write it to a CSV file. It creates a day-by-day simulation with:
- Piecewise-linear daily order counts (anchored at specific dates).
- Random variations in daily order counts (noise).
- Random order values and payment methods.

Use it to seed an application with realistic-looking example order data.
## Table of Contents
1. [Requirements](#requirements)
2. [Usage](#usage)
3. [How It Works](#how-it-works)
4. [Output Format](#output-format)
5. [Notes](#notes)

## Requirements
- PHP 8.2 (or newer) with CLI interface.
- The [DateTimeImmutable](https://www.php.net/manual/en/class.datetimeimmutable.php) class, which is standard in modern PHP versions.
- Write permissions in the directory where the CSV file is created.

## Usage
Make the file executable (optional):
``` bash
   chmod +x generate_demo_orders.php
```
Run the script:
``` bash
   ./generate_demo_orders.php
```
Alternatively:
``` bash
   php generate_demo_orders.php
```

Upon completion, it writes the file “demo_orders.csv” in the same directory with generated order data.

## How It Works
1. **Anchors:** The script has an array of “anchor” dates, each with a desired daily order count.
2. **Interpolation:** For any date between the earliest and latest anchor, it computes an approximate daily order count based on piecewise-linear interpolation.
3. **Random Noise:** Each day’s order count is adjusted by a small random offset, with a minimum daily count of 4.
4. **Order Details:**
    - Timestamp: Assigned randomly throughout the day.
    - Brutto Revenue: Based on a daily mean value, increasing slowly over time, with random variation.
    - Number of items: 1 to 10, randomly selected.
    - Payment Method: Randomly chosen from a small set of demo methods.

## Output Format
The script outputs a CSV with the following columns:
1. `order_number` — A sequentially increasing identifier (ORD-0001, ORD-0002, etc.).
2. `order_date` — Date and time of the order in “YYYY-MM-DD HH:MM:SS” format.
3. `order_brutto` — Randomly generated order amount in float format.
4. `order_items_count` — Number of items included in the order.
5. `order_payment_method_id` — One of several hard-coded payment method IDs.

## Notes
- This is a demo generator; it does not connect to any real database.
- You can tune anchors, daily noise ranges, or other parameters as desired.
- If you need to regenerate data, simply run the script again to produce a fresh CSV.

Enjoy exploring the demo data!
