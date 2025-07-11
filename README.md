# Obike-tech

## Project Overview
Obike-tech is a comprehensive business management system built with Laravel and Vue.js. It is designed to streamline operations for organizations involved in equipment rentals, procurement, project management, human resources, supplier management, and financial tracking.

## Features
- Equipment Rentals Management
- Procurement and Supplier Management
- Project and Task Tracking
- Human Resource Management
- Financial Reporting and Payments
- Role-based Access Control
- Interactive Dashboards
- PDF Generation for Agreements

## Technologies Used
- PHP (Laravel Framework)
- JavaScript (Vue.js)
- MySQL or compatible database
- Composer for dependency management
- Git for version control

## Installation
1. **Clone the repository:**
   ```bash
   git clone https://github.com/masad-stock/Obike-tech.git
   cd Obike-tech
   ```
2. **Install dependencies:**
   ```bash
   composer install
   npm install
   ```
3. **Copy the environment file and set up your environment:**
   ```bash
   cp .env.example .env
   # Edit .env with your database and mail settings
   ```
4. **Generate application key:**
   ```bash
   php artisan key:generate
   ```
5. **Run migrations and seeders:**
   ```bash
   php artisan migrate --seed
   ```
6. **Build frontend assets:**
   ```bash
   npm run dev
   ```
7. **Start the development server:**
   ```bash
   php artisan serve
   ```

## Usage
- Access the application at `http://localhost:8000` after starting the server.
- Log in with the credentials created during seeding or registration.
- Navigate through the dashboard to manage rentals, projects, procurement, HR, and more.

## Contribution
Contributions are welcome! Please fork the repository and submit a pull request. For major changes, open an issue first to discuss what you would like to change.

## License
This project is licensed under the MIT License. See the LICENSE file for details.
