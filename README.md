- composer install để download các thư viện
- cp .env.example .env 
- php artisan migrate
- php artisan install:api: để sử dụng api.php

## Tài khoản seeder

Tất cả tài khoản seed mặc định đều dùng mật khẩu: `password`.

| Role          | Email                             |
| ---           | ---                               |
| Admin         | `admin@ticketrush.com`            |
| Organizer     | `organizer.music@ticketrush.com`  |
| Organizer     | `organizer.sport@ticketrush.com`  |
| Customer      | `customer@ticketrush.com`         |
| Customer      | `customer2@ticketrush.com`        |
