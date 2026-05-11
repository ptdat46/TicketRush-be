# TicketRush Auth API - Manual Test Guide

## 1. Customer register

`POST /api/auth/register/customer`

```json
{
  "name": "Nguyen Van A",
  "email": "customer@example.com",
  "password": "password",
  "password_confirmation": "password",
  "gender": "male",
  "birthday": "2000-01-01"
}
```

## 2. Organizer register

`POST /api/auth/register/organizer`

```json
{
  "name": "Tran Van B",
  "email": "organizer@example.com",
  "password": "password",
  "password_confirmation": "password",
  "organizer_name": "ABC Event Company",
  "tax_code": "0123456789"
}
```

## 3. Verify email

`POST /api/auth/verify`

```json
{
  "email": "customer@example.com",
  "code": "123456"
}
```

After verification, API returns a Sanctum token.

## 4. Resend verification code

`POST /api/auth/resend-code`

```json
{
  "email": "customer@example.com"
}
```

## 5. Login

`POST /api/auth/login`

```json
{
  "email": "customer@example.com",
  "password": "password"
}
```

Unverified accounts cannot login. The API will resend a verification code.

## 6. Admin login

Default seeded admin account:

```txt
email: admin@ticketrush.com
password: password
```

## 7. Authenticated profile

`GET /api/auth/me`

Header:

```txt
Authorization: Bearer <token>
Accept: application/json
```

## 8. Logout

`POST /api/auth/logout`

Header:

```txt
Authorization: Bearer <token>
Accept: application/json
```

## 9. Role middleware test endpoints

All require `Authorization: Bearer <token>`.

- `GET /api/admin/ping`: only `admin`
- `GET /api/organizer/ping`: only `organizer`
- `GET /api/customer/ping`: only `customer`

## 10. Required commands before manual test

```powershell
php artisan migrate
php artisan db:seed --class=AdminSeeder
```

## 11. Mail configuration

Set real SMTP values in `.env` to receive emails. For local testing, you can use `MAIL_MAILER=log` and read the code in `storage/logs/laravel.log`.
