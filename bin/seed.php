<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Domain\Entity\Hotel;
use App\Domain\Entity\Room;
use App\Domain\Enum\RoomType;
use App\Domain\ValueObject\Address;
use App\Domain\ValueObject\Capacity;
use App\Domain\ValueObject\Currency;
use App\Domain\ValueObject\GeoPoint;
use App\Domain\ValueObject\HotelId;
use App\Domain\ValueObject\HotelName;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\RoomId;
use App\Domain\ValueObject\RoomNumber;
use App\Domain\ValueObject\StarRating;
use App\Domain\ValueObject\UserId;
use App\Infrastructure\Repository\Pdo\PdoHotelRepository;
use App\Infrastructure\Repository\Pdo\PdoRoomRepository;

$dsn = sprintf(
    'pgsql:host=%s;port=%s;dbname=%s',
    getenv('DB_HOST') ?: 'localhost',
    getenv('DB_PORT') ?: '5432',
    getenv('DB_NAME') ?: 'hotel_booking',
);
$user = (string) (getenv('DB_USER') ?: 'hotel_user');
$pass = (string) (getenv('DB_PASSWORD') ?: 'secret');

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (\PDOException $e) {
    fwrite(STDERR, 'Connection failed: ' . $e->getMessage() . "\n");
    exit(1);
}

$hotels = new PdoHotelRepository($pdo);
$rooms  = new PdoRoomRepository($pdo);

// Fixed manager UUID — stands in for a real user until Week 5 auth
$managerId = new UserId('11111111-1111-4111-8111-111111111111');

// -----------------------------------------------------------------------
// Hotels
// -----------------------------------------------------------------------

$data = [
    [
        'id'          => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
        'name'        => 'Grand Kyiv Hotel',
        'description' => 'Flagship luxury hotel in the heart of Kyiv, steps from the Maidan.',
        'street'      => 'Khreshchatyk St, 1',
        'city'        => 'Kyiv',
        'country'     => 'Ukraine',
        'postalCode'  => '01001',
        'lat'         => 50.4501,
        'lng'         => 30.5234,
        'stars'       => 5,
        'rooms'       => [
            ['id' => 'a0000000-0000-4000-8000-000000000001', 'number' => '101', 'type' => RoomType::Single,  'capacity' => 1, 'price' => 8000],
            ['id' => 'a0000000-0000-4000-8000-000000000002', 'number' => '102', 'type' => RoomType::Double,  'capacity' => 2, 'price' => 15000],
            ['id' => 'a0000000-0000-4000-8000-000000000003', 'number' => '201', 'type' => RoomType::Double,  'capacity' => 2, 'price' => 18000],
            ['id' => 'a0000000-0000-4000-8000-000000000004', 'number' => '301', 'type' => RoomType::Suite,   'capacity' => 4, 'price' => 45000],
            ['id' => 'a0000000-0000-4000-8000-000000000005', 'number' => '401', 'type' => RoomType::Deluxe,  'capacity' => 3, 'price' => 30000],
        ],
    ],
    [
        'id'          => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb',
        'name'        => 'Paris Palace',
        'description' => 'Elegant Haussmann-era hotel near the Louvre with views of the Seine.',
        'street'      => '12 Rue de Rivoli',
        'city'        => 'Paris',
        'country'     => 'France',
        'postalCode'  => '75001',
        'lat'         => 48.8566,
        'lng'         => 2.3522,
        'stars'       => 4,
        'rooms'       => [
            ['id' => 'b0000000-0000-4000-8000-000000000001', 'number' => '101', 'type' => RoomType::Single,  'capacity' => 1, 'price' => 12000],
            ['id' => 'b0000000-0000-4000-8000-000000000002', 'number' => '102', 'type' => RoomType::Double,  'capacity' => 2, 'price' => 22000],
            ['id' => 'b0000000-0000-4000-8000-000000000003', 'number' => '201', 'type' => RoomType::Suite,   'capacity' => 4, 'price' => 60000],
        ],
    ],
    [
        'id'          => 'cccccccc-cccc-4ccc-8ccc-cccccccccccc',
        'name'        => 'Berlin Boutique',
        'description' => 'Cosy design hotel in Mitte, walking distance from Museum Island.',
        'street'      => 'Unter den Linden 5',
        'city'        => 'Berlin',
        'country'     => 'Germany',
        'postalCode'  => '10117',
        'lat'         => 52.5200,
        'lng'         => 13.4050,
        'stars'       => 3,
        'rooms'       => [
            ['id' => 'c0000000-0000-4000-8000-000000000001', 'number' => '101', 'type' => RoomType::Single,  'capacity' => 1, 'price' => 6500],
            ['id' => 'c0000000-0000-4000-8000-000000000002', 'number' => '102', 'type' => RoomType::Double,  'capacity' => 2, 'price' => 10000],
            ['id' => 'c0000000-0000-4000-8000-000000000003', 'number' => '201', 'type' => RoomType::Double,  'capacity' => 2, 'price' => 11000],
        ],
    ],
];

foreach ($data as $h) {
    $hotel = new Hotel(
        new HotelId($h['id']),
        new HotelName($h['name']),
        $h['description'],
        new Address($h['street'], $h['city'], $h['country'], $h['postalCode']),
        new GeoPoint($h['lat'], $h['lng']),
        new StarRating($h['stars']),
        $managerId,
    );

    $hotels->save($hotel);
    echo "  [hotel] {$h['name']}\n";

    foreach ($h['rooms'] as $r) {
        $room = new Room(
            new RoomId($r['id']),
            new HotelId($h['id']),
            $r['type'],
            new RoomNumber($r['number']),
            new Capacity($r['capacity']),
            new Money($r['price'], new Currency('USD')),
        );

        $rooms->save($room);
        echo "    [room] {$r['number']} ({$r['type']->value}, {$r['capacity']} guest(s), \${$r['price']} cents/night)\n";
    }
}

echo "\nSeed complete. " . count($data) . " hotels, " . array_sum(array_column(array_map(fn ($h) => ['c' => count($h['rooms'])], $data), 'c')) . " rooms.\n";