# VCY Accounting

A modern accounting system web application built with **Laravel**, **Inertia.js**, **Svelte 4**, **Shadcn-Svelte**, and **Tailwind CSS v4**.

---

## 🚀 Local Development Setup (Docker & Laravel Sail)

Follow these steps to get the application running locally on your computer.

### Prerequisites
- [Docker Desktop](https://www.docker.com/products/docker-desktop/) installed and running.
- [Git](https://git-scm.com/) installed.

---

### Step-by-Step Installation

#### 1. Clone the Repository
```bash
git clone git@github.com:dwiazizmf/vcy-akunting.git
cd vcy-akunting
```

#### 2. Set Up Environment Variables
Copy the example environment file:
```bash
cp .env.example .env
```
*Note: The default `.env.example` is configured to work out-of-the-box with Laravel Sail's services (PostgreSQL, Redis, etc.).*

#### 3. Install Dependencies (If `vendor/` or `node_modules/` is missing)
If you just cloned the repository and do not have Composer installed on your host machine, you can install the PHP dependencies using a temporary Docker container:
```bash
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php8.5-composer:latest \
    composer install --ignore-platform-reqs
```
Install NPM packages:
```bash
npm install
```

#### 4. Configure Laravel Sail Alias (Optional)
To avoid typing `./vendor/bin/sail` for every command, add an alias to your shell configuration (`~/.bashrc` or `~/.zshrc`):
```bash
echo "alias sail='./vendor/bin/sail'" >> ~/.bashrc
source ~/.bashrc
```

#### 5. Start the Docker Containers
Start the Sail server in detached mode (background):
```bash
sail up -d
```
This starts:
- **Laravel Application** (`http://localhost`)
- **PostgreSQL Database** (Port `5432`)
- **Redis Cache/Queue** (Port `6379`)

#### 6. Run Database Migrations and Seeders
Run the migrations to create the database schema:
```bash
sail artisan migrate --seed
```

#### 7. Start the Vite Dev Server
Start the frontend compiler for live hot-reloading:
```bash
sail npm run dev
```

Open `http://localhost` in your web browser.

---

## 🛠️ Common Sail Development Commands

- **Stop all services:**
  ```bash
  sail down
  ```
- **Run Artisan commands:**
  ```bash
  sail artisan <command>
  ```
  *(Example: `sail artisan make:controller InvoiceController`)*
- **Run Composer commands:**
  ```bash
  sail composer <command>
  ```
- **Access the container shell:**
  ```bash
  sail shell
  ```

---

## 🏭 Production Deployment

When deploying to a production server, follow these steps to ensure a secure, fast, and optimized application.

### 1. Set Up Production `.env`
Update your production `.env` with the following critical settings:
```ini
APP_NAME="VCY Accounting"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://accounting.vcy.co.id # Replace with your production domain

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1 # Or Docker service name
DB_PORT=5432
DB_DATABASE=vcy_accounting
DB_USERNAME=your_secure_user
DB_PASSWORD=your_secure_password
```

### 2. Deploy Code and Install Dependencies
Pull the latest code and install dependencies without development tools:
```bash
git pull origin main

# Install optimized PHP dependencies
composer install --no-dev --optimize-autoloader

# Install and build frontend assets
npm install
npm run build
```

### 3. Folder Permissions
Ensure the web server (usually `www-data`) has write permissions to the storage and bootstrap cache directories:
```bash
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

### 4. Cache Configurations for Speed
Run these cache commands to compile configurations, routes, and views:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

### 5. Run Database Migrations
Run the migrations safely with the `--force` flag to avoid confirmation prompts:
```bash
php artisan migrate --force
```

### 6. Process Queue / Background Jobs (Supervisor)
If you use queues for background processes, configure a process manager like **Supervisor** to keep the queue worker running:
```ini
[program:vcy-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/html/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/html/storage/logs/worker.log
stopwaitsecs=3600
```
