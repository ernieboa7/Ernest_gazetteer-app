# Gazetteer App (Project 1)

A mobile-first, full-screen single-page application to search and explore information about places and countries.

## Features
- Leaflet map (full viewport) with markers
- Search by place name (OpenCage) or coordinates
- Nearby place (GeoNames `findNearbyPlaceNameJSON`)
- Local time & timezone (GeoNames `timezoneJSON`)
- Current weather (OpenWeather)
- Bootstrap 5 UI with sticky nav, accordion side panel, and toasts
- PHP cURL proxies to hide API keys and avoid CORS
- Optional search history (LocalStorage)

## Getting Started

### 1) Provide API credentials via environment variables
Set these on your PHP server process (or in an `.env` loader if you have one):

- `OPEN_CAGE_KEY` – OpenCage Geocoding API key
- `GEONAMES_USERNAME` – GeoNames username
- `OPEN_WEATHER_KEY` – OpenWeather API key

> Security: Do **not** hardcode keys in frontend JavaScript.

### 2) Serve with PHP
From the project root, run a local PHP dev server:

```bash
php -S localhost:8080
```

Then open http://localhost:8080 in your browser.

### 3) Data files
- `data/countryBorders.geo.json` – placeholder FeatureCollection. Replace with your full dataset if needed.

### 4) Notes
- This starter focuses on the **core** endpoints you listed:
  - `searchJSON` (you can use OpenCage for geocoding; if you prefer GeoNames `searchJSON`, add a branch in `php/geonames.php`)
  - `findNearbyPlaceNameJSON`
  - `timezoneJSON`
  - `findNearByWeatherJSON` (we use OpenWeather for richer data; you can switch to GeoNames weather by changing `fetchWeather`)
- Add more APIs (REST Countries, Open Exchange Rates) as needed—consider adding separate PHP proxies for each.

### 5) Error handling
- Frontend shows Bootstrap Toast notifications.
- PHP proxies return JSON on failures with HTTP status set.

## File Structure
```
gazetteer-app/
├── index.html
├── js/
│   └── app.js
├── css/
│   └── style.css
├── data/
│   └── countryBorders.geo.json
├── php/
│   ├── opencage.php
│   ├── geonames.php
│   └── weather.php
├── images/
│   ├── flags/
│   └── icons/
└── README.md
```

## Next steps & enhancements
- REST Countries panel (flag, currency, population, languages), via `php/restcountries.php` proxy
- Exchange rates (Open Exchange Rates) for currency conversions
- Country borders overlay using `data/countryBorders.geo.json`
- Custom Leaflet icons + animated transitions
- Progressive enhancement: cache recent queries in `localStorage` with timestamps
