<?php

declare(strict_types=1);

$timezone = Environment::get('ROYALBREAD_TIMEZONE', 'Asia/Ho_Chi_Minh');
$dbTimezone = trim(Environment::get('ROYALBREAD_DB_TIMEZONE', ''));

try {
    $timeZoneObject = new DateTimeZone($timezone);
} catch (Throwable) {
    $timezone = 'Asia/Ho_Chi_Minh';
    $timeZoneObject = new DateTimeZone($timezone);
}

if ($dbTimezone === '') {
    $offsetSeconds = $timeZoneObject->getOffset(new DateTimeImmutable('now', $timeZoneObject));
    $offsetHours = (int) floor(abs($offsetSeconds) / 3600);
    $offsetMinutes = (int) floor((abs($offsetSeconds) % 3600) / 60);
    $dbTimezone = sprintf('%s%02d:%02d', $offsetSeconds >= 0 ? '+' : '-', $offsetHours, $offsetMinutes);
}

return [
    'app_name' => 'RoyalBread',
    'timezone' => $timezone,
    'db_timezone' => $dbTimezone,
];
