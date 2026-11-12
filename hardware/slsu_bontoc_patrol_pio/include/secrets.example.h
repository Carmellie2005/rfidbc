#pragma once

// Copy this file to include/secrets.h and put the real WiFi details there.
// include/secrets.h is ignored by Git.
#define WIFI_SSID "YOUR_WIFI_NAME"
#define WIFI_PASSWORD "YOUR_WIFI_PASSWORD"

// Generate with: php artisan rfid:issue-device-token ESP32-REG-01 --enrollment
// Keep the real token private and never commit it to GitHub.
#define DEVICE_API_TOKEN "PASTE_DEVICE_TOKEN_HERE"
