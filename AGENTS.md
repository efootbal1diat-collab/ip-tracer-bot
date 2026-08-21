# AGENTS.md

## Project Shape
- Laravel 13 app with PHP `^8.3` for IP network tracing and management.
- The dashboard is served by `app/Http/Controllers/IpTracingController.php` through `routes/web.php`.
- The interface is a standalone Blade view at `resources/views/ip_tracing/dashboard.blade.php`; it does not use Vue or Vite.

## Commands
- Install PHP dependencies: `composer install`.
- One-shot setup: `composer run setup`.
- Full local development stack: `composer run dev`.
- Tests: `composer run test`.
- PHP formatting: `vendor/bin/pint`.

## Application Behavior
- The dashboard scans the `172.16.250.1-254` subnet and shows online status, hostname, MAC address, vendor, probable device, response time, and open ports.
- IP records are read from and updated in `001. Data User IP.xlsx` by `app/Services/IpExcelService.php`.
- `app/Services/NetworkScannerService.php` handles ping, ARP, hostname, and port scanning; do not run broad scans against networks outside the authorized subnet.
- `app/Services/MacVendorLookupService.php` classifies a device using its MAC address.

## Code Style
- Use 4-space indentation, UTF-8, LF line endings, and a trailing newline. See `.editorconfig`.
