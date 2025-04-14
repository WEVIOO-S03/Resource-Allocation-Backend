# Backend Repository

This repository contains the Symfony backend application with an admin panel generated.

## Prerequisites

- PHP 8.x
- Composer
- PostgreSQL 16
- JWT key pair for authentication

## Installation & Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/WEVIOO-S03/Resource-Allocation-Backend.git
   cd Resource-Allocation-Backend
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Configure environment**
   ```bash
   cp .env .env.local
   ```
   
   Edit `.env.local` to set your database connection and JWT passphrase:
   ```
   APP_ENV=dev
   APP_SECRET=1cb28f4ae9f834b642ab9d8e85870534
   DATABASE_URL="postgresql://root:root@127.0.0.1:5432/app?serverVersion=16&charset=utf8"
   JWT_PASSPHRASE=your_secure_passphrase
   ```

4. **Generate JWT keys**
   ```bash
   mkdir -p config/jwt
   openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096
   openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout
   ```

5. **Set up the database**
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```

6. **Load fixtures (optional)**
   ```bash
   php bin/console doctrine:fixtures:load
   ```

7. **Create an admin user**
   ```bash
   php bin/console app:create-admin 
   ```
   Add `admin@wevioo.com` and `password` with your desired admin credentials.

## Running the Application

### Using PHP's built-in server
```bash
php -S localhost:8000 -t public/
```
