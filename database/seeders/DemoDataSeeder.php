<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Seat;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $organizers = $this->seedOrganizers();
        $customers = $this->seedCustomers();
        $events = $this->seedEvents($organizers);
        $this->seedZonesAndSeats($events, $customers);
    }

    private function seedOrganizers(): array
    {
        return [
            'music' => User::updateOrCreate(
                ['email' => 'organizer.music@ticketrush.com'],
                [
                    'name' => 'Minh Anh Organizer',
                    'password' => Hash::make('password'),
                    'role' => 'organizer',
                    'organizer_name' => 'TicketRush Music Live',
                    'tax_code' => 'TR-MUSIC-001',
                    'email_verified_at' => now(),
                ]
            ),
            'sport' => User::updateOrCreate(
                ['email' => 'organizer.sport@ticketrush.com'],
                [
                    'name' => 'Quang Huy Organizer',
                    'password' => Hash::make('password'),
                    'role' => 'organizer',
                    'organizer_name' => 'TicketRush Sports Hub',
                    'tax_code' => 'TR-SPORT-001',
                    'email_verified_at' => now(),
                ]
            ),
        ];
    }

    private function seedCustomers(): array
    {
        return [
            'customer' => User::updateOrCreate(
                ['email' => 'customer@ticketrush.com'],
                [
                    'name' => 'Nguyen Van Customer',
                    'password' => Hash::make('password'),
                    'role' => 'customer',
                    'gender' => 'male',
                    'birthday' => '2000-01-01',
                    'email_verified_at' => now(),
                ]
            ),
            'customer2' => User::updateOrCreate(
                ['email' => 'customer2@ticketrush.com'],
                [
                    'name' => 'Tran Thi Customer',
                    'password' => Hash::make('password'),
                    'role' => 'customer',
                    'gender' => 'female',
                    'birthday' => '2001-05-20',
                    'email_verified_at' => now(),
                ]
            ),
        ];
    }

    private function seedEvents(array $organizers): array
    {
        $eventData = [
            [
                'organizer_id' => $organizers['music']->id,
                'name' => 'Neon Nights Festival 2024',
                'description' => 'Đêm nhạc điện tử bùng nổ với dàn line-up quốc tế cực khủng.',
                'category' => 'dj',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1501386761578-eac5c94b800a',
                'banner_url' => 'https://images.unsplash.com/photo-1492684223066-81342ee5ff30',
                'is_featured' => true,
                'is_special' => true,
                'sort_order' => 1,
                'venue' => 'Nhà thi đấu Phú Thọ, TP.HCM',
                'starts_at' => now()->addDays(20)->setTime(20, 0),
                'ends_at' => now()->addDays(20)->setTime(23, 30),
                'ticket_sale_starts_at' => now()->subDays(5)->setTime(10, 0),
                'ticket_sale_ends_at' => now()->addDays(18)->setTime(23, 59),
                'status' => 'approved',
                'display_type' => 'stadium',
                'master_width' => 80,
                'master_length' => 60,
            ],
            [
                'organizer_id' => $organizers['sport']->id,
                'name' => 'Chung Kết Cúp Bóng Đá Vô Địch Quốc Gia',
                'description' => 'Trận thư hùng quyết định ngôi vương của mùa giải.',
                'category' => 'sport',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1431324155629-1a6deb1dec8d',
                'banner_url' => 'https://images.unsplash.com/photo-1577223625816-7546f13df25d',
                'is_featured' => true,
                'is_special' => true,
                'sort_order' => 2,
                'venue' => 'Sân vận động Quốc gia Mỹ Đình, Hà Nội',
                'starts_at' => now()->addDays(30)->setTime(19, 30),
                'ends_at' => now()->addDays(30)->setTime(22, 0),
                'ticket_sale_starts_at' => now()->subDays(3)->setTime(9, 0),
                'ticket_sale_ends_at' => now()->addDays(28)->setTime(23, 59),
                'status' => 'approved',
                'display_type' => 'stadium',
                'master_width' => 100,
                'master_length' => 70,
            ],
            [
                'organizer_id' => $organizers['music']->id,
                'name' => 'The Midnight Sounds - Asia Tour 2024',
                'description' => 'Tour diễn live band với âm thanh và ánh sáng đỉnh cao.',
                'category' => 'music',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1540039155733-5bb30b53aa14',
                'banner_url' => 'https://images.unsplash.com/photo-1524368535928-5b5e00ddc76b',
                'is_featured' => false,
                'is_special' => false,
                'sort_order' => 3,
                'venue' => 'Nhà thi đấu Phú Thọ, TP.HCM',
                'starts_at' => now()->addDays(12)->setTime(20, 0),
                'ends_at' => now()->addDays(12)->setTime(22, 30),
                'ticket_sale_starts_at' => now()->subDays(2)->setTime(8, 0),
                'ticket_sale_ends_at' => now()->addDays(10)->setTime(23, 59),
                'status' => 'approved',
                'display_type' => 'rectangular',
                'master_width' => 70,
                'master_length' => 50,
            ],
            [
                'organizer_id' => $organizers['music']->id,
                'name' => 'Tech Summit Vietnam: AI & Tương lai',
                'description' => 'Hội nghị công nghệ về AI, dữ liệu và sản phẩm số.',
                'category' => 'conference',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1540575467063-178a50c2df87',
                'banner_url' => 'https://images.unsplash.com/photo-1515169067865-5387ec356754',
                'is_featured' => false,
                'is_special' => false,
                'sort_order' => 4,
                'venue' => 'GEM Center, TP.HCM',
                'starts_at' => now()->addDays(18)->setTime(8, 0),
                'ends_at' => now()->addDays(18)->setTime(17, 0),
                'ticket_sale_starts_at' => now()->addDays(1)->setTime(10, 0),
                'ticket_sale_ends_at' => now()->addDays(16)->setTime(23, 59),
                'status' => 'approved',
                'display_type' => 'rectangular',
                'master_width' => 60,
                'master_length' => 40,
            ],
            [
                'organizer_id' => $organizers['music']->id,
                'name' => 'Ravetopia 2024: Đêm Giao Thừa',
                'description' => 'Đại nhạc hội countdown với DJ, laser và pháo hoa.',
                'category' => 'dj',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1571266028243-d220c6a7edbf',
                'banner_url' => 'https://images.unsplash.com/photo-1506157786151-b8491531f063',
                'is_featured' => false,
                'is_special' => false,
                'sort_order' => 5,
                'venue' => 'Khu Đô Thị Sala, TP.HCM',
                'starts_at' => now()->addDays(40)->setTime(19, 0),
                'ends_at' => now()->addDays(41)->setTime(1, 0),
                'ticket_sale_starts_at' => now()->addDays(10)->setTime(10, 0),
                'ticket_sale_ends_at' => now()->addDays(38)->setTime(23, 59),
                'status' => 'approved',
                'display_type' => 'arc',
                'master_width' => 90,
                'master_length' => 55,
            ],
            [
                'organizer_id' => $organizers['music']->id,
                'name' => 'Vở Nhạc Kịch: Tiếng Gọi Nơi Hoang Dã',
                'description' => 'Tác phẩm sân khấu nghệ thuật dành cho người yêu nhạc kịch.',
                'category' => 'theater',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1503095396549-807759245b35',
                'banner_url' => 'https://images.unsplash.com/photo-1507676184212-d03ab07a01bf',
                'is_featured' => false,
                'is_special' => false,
                'sort_order' => 6,
                'venue' => 'Nhà Hát Thành Phố, TP.HCM',
                'starts_at' => now()->addDays(9)->setTime(20, 0),
                'ends_at' => now()->addDays(9)->setTime(22, 0),
                'ticket_sale_starts_at' => now()->subDays(1)->setTime(9, 0),
                'ticket_sale_ends_at' => now()->addDays(7)->setTime(23, 59),
                'status' => 'approved',
                'display_type' => 'rectangular',
                'master_width' => 50,
                'master_length' => 40,
            ],
            [
                'organizer_id' => $organizers['sport']->id,
                'name' => 'Workshop Sáng tạo Nội dung 2024',
                'description' => 'Workshop thực hành xây dựng nội dung số và thương hiệu cá nhân.',
                'category' => 'workshop',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1552664730-d307ca884978',
                'banner_url' => 'https://images.unsplash.com/photo-1517245386807-bb43f82c33c4',
                'is_featured' => false,
                'is_special' => false,
                'sort_order' => 7,
                'venue' => 'Dreamplex Nguyễn Trung Ngạn, TP.HCM',
                'starts_at' => now()->addDays(14)->setTime(9, 0),
                'ends_at' => now()->addDays(14)->setTime(12, 0),
                'ticket_sale_starts_at' => now()->addDays(5)->setTime(10, 0),
                'ticket_sale_ends_at' => now()->addDays(12)->setTime(23, 59),
                'status' => 'pending',
                'display_type' => 'rectangular',
                'master_width' => 40,
                'master_length' => 30,
            ],
            [
                'organizer_id' => $organizers['sport']->id,
                'name' => 'Comedy Night: Cười Xuyên Đêm',
                'description' => 'Đêm hài độc thoại với các nghệ sĩ trẻ.',
                'category' => 'comedy',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1527224857830-43a7acc85260',
                'banner_url' => 'https://images.unsplash.com/photo-1516450360452-9312f5e86fc7',
                'is_featured' => false,
                'is_special' => false,
                'sort_order' => 8,
                'venue' => 'Sân khấu Idecaf, TP.HCM',
                'starts_at' => now()->addDays(7)->setTime(19, 30),
                'ends_at' => now()->addDays(7)->setTime(21, 30),
                'ticket_sale_starts_at' => now()->subDays(10)->setTime(9, 0),
                'ticket_sale_ends_at' => now()->subDays(1)->setTime(23, 59),
                'status' => 'rejected',
                'display_type' => 'rectangular',
                'master_width' => 45,
                'master_length' => 35,
            ],
        ];

        return collect($eventData)
            ->mapWithKeys(function (array $data): array {
                $event = Event::updateOrCreate(
                    ['name' => $data['name']],
                    $data
                );

                return [$event->name => $event];
            })
            ->all();
    }

    private function seedZonesAndSeats(array $events, array $customers): void
    {
        foreach ($events as $event) {
            $vipZone = Zone::updateOrCreate(
                ['event_id' => $event->id, 'name' => 'VIP'],
                [
                    'price' => 1500000,
                    'color' => '#10B981',
                    'icon_url' => null,
                    'pos_x' => 5,
                    'pos_y' => 5,
                    'width' => 8,
                    'length' => 5,
                    'is_seating' => true,
                ]
            );

            $standardZone = Zone::updateOrCreate(
                ['event_id' => $event->id, 'name' => 'Standard'],
                [
                    'price' => 750000,
                    'color' => '#3B82F6',
                    'icon_url' => null,
                    'pos_x' => 15,
                    'pos_y' => 12,
                    'width' => 10,
                    'length' => 6,
                    'is_seating' => true,
                ]
            );

            Zone::updateOrCreate(
                ['event_id' => $event->id, 'name' => 'Lối đi trung tâm'],
                [
                    'price' => 0,
                    'color' => '#6B7280',
                    'icon_url' => null,
                    'pos_x' => 13,
                    'pos_y' => 5,
                    'width' => 2,
                    'length' => 13,
                    'is_seating' => false,
                ]
            );

            $this->seedSeatsForZone($vipZone, 5, 8, $customers['customer']->id);
            $this->seedSeatsForZone($standardZone, 6, 10, $customers['customer2']->id);
        }
    }

    private function seedSeatsForZone(Zone $zone, int $rows, int $cols, int $lockedBy): void
    {
        for ($row = 1; $row <= $rows; $row++) {
            for ($col = 1; $col <= $cols; $col++) {
                $status = 'available';
                $lockedByUserId = null;
                $lockedAt = null;

                if ($row === 1 && $col <= 2) {
                    $status = 'sold';
                }

                if ($row === 2 && $col <= 2) {
                    $status = 'locked';
                    $lockedByUserId = $lockedBy;
                    $lockedAt = now()->subMinutes(3);
                }

                Seat::updateOrCreate(
                    [
                        'zone_id' => $zone->id,
                        'row_index' => $row,
                        'col_index' => $col,
                    ],
                    [
                        'status' => $status,
                        'locked_by' => $lockedByUserId,
                        'locked_at' => $lockedAt,
                    ]
                );
            }
        }
    }
}
