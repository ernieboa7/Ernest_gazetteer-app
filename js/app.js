/* Gazetteer SPA - Final Version
 * Uses Bootstrap 5 + Leaflet
 * Proxies: php/opencage.php, php/geonames.php, php/weather.php, php/restcountries.php
 */
(() => {
  const map = L.map('map', { zoomControl: true });
  const osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; OpenStreetMap contributors'
  }).addTo(map);

  const el = {
    userBadge: document.getElementById('userLocationBadge'),
    loader: document.getElementById('globalLoader'),
    toast: document.getElementById('toast'),
    toastBody: document.getElementById('toastBody'),
    placeInfo: document.getElementById('placeInfo'),
    weatherInfo: document.getElementById('weatherInfo'),
    timeInfo: document.getElementById('timeInfo'),
    historyList: document.getElementById('historyList'),
    placeInput: document.getElementById('placeInput'),
    latInput: document.getElementById('latInput'),
    lngInput: document.getElementById('lngInput'),
    btnUseMyLocation: document.getElementById('btnUseMyLocation'),
    btnSearchCoords: document.getElementById('btnSearchCoords'),
    btnClear: document.getElementById('btnClear'),
    form: document.getElementById('searchForm'),
  };

  const toast = new bootstrap.Toast(el.toast);
  const markers = L.layerGroup().addTo(map);

  // Loader + Notification
  function showLoader(show = true) { el.loader.classList.toggle('d-none', !show); }
  function notify(msg) { el.toastBody.textContent = msg; toast.show(); }

  // Save search history (localStorage)
  function saveHistory(item) {
    const key = 'gazetteer_history';
    const list = JSON.parse(localStorage.getItem(key) || '[]');
    list.unshift({ ...item, ts: Date.now() });
    const dedup = [];
    const seen = new Set();
    for (const it of list) {
      const k = it.name ?? `${it.lat},${it.lng}`;
      if (seen.has(k)) continue;
      seen.add(k);
      dedup.push(it);
      if (dedup.length >= 10) break;
    }
    localStorage.setItem(key, JSON.stringify(dedup));
    renderHistory();
  }

  function renderHistory() {
    const key = 'gazetteer_history';
    const list = JSON.parse(localStorage.getItem(key) || '[]');
    el.historyList.innerHTML = '';
    for (const it of list) {
      const li = document.createElement('li');
      li.className = 'list-group-item d-flex justify-content-between align-items-center';
      li.innerHTML = `<span>${it.name ?? it.lat + ', ' + it.lng}</span><button class="btn btn-sm btn-outline-primary">Load</button>`;
      li.querySelector('button').addEventListener('click', () => {
        if (it.name) handlePlaceSearch(it.name);
        else handleCoordSearch(it.lat, it.lng);
      });
      el.historyList.appendChild(li);
    }
  }

  // Generic GET helper
  async function getJSON(url, params = {}) {
    const u = new URL(url, window.location.origin);
    Object.entries(params).forEach(([k, v]) => v !== undefined && v !== null && u.searchParams.append(k, v));
    const res = await fetch(u.toString(), { headers: { 'Accept': 'application/json' } });
    if (!res.ok) throw new Error(`Request failed: ${res.status}`);
    return await res.json();
  }

  // UI Update Helpers
  function updatePlaceInfo(place) {
    if (!place) { el.placeInfo.innerHTML = '<em>No location selected.</em>'; return; }
    const nearbyCity = place.nearby?.name ? `${place.nearby.name}, ${place.nearby.countryName}` : '—';
    const lines = [
      `<div><strong>Selected:</strong> ${place.displayName ?? '—'}</div>`,
      `<div><strong>Coordinates:</strong> ${place.lat?.toFixed?.(5) ?? place.lat}, ${place.lng?.toFixed?.(5) ?? place.lng}</div>`,
      `<div><strong>Nearest place:</strong> ${nearbyCity} (${place.nearby?.distance?.toFixed?.(1) ?? '—'} km)</div>`,
      place.countryCode ? `<div><strong>Country code:</strong> ${place.countryCode}</div>` : '',
    ].filter(Boolean);
    el.placeInfo.innerHTML = lines.join('');
  }

  function updateWeatherInfo(weather) {
    if (!weather) { el.weatherInfo.innerHTML = '<em>No weather yet.</em>'; return; }
    const lines = [
      `<div><strong>Temperature:</strong> ${weather.temp}°C</div>`,
      `<div><strong>Humidity:</strong> ${weather.humidity}%</div>`,
      `<div><strong>Conditions:</strong> ${weather.description}</div>`,
      weather.wind ? `<div><strong>Wind:</strong> ${weather.wind} m/s</div>` : '',
    ].filter(Boolean);
    el.weatherInfo.innerHTML = lines.join('');
  }

  function updateTimeInfo(info) {
    if (!info) { el.timeInfo.innerHTML = '<em>No time/zone yet.</em>'; return; }
    const dt = new Date(info.localTime);
    const lines = [
      `<div><strong>Local time:</strong> ${dt.toLocaleString()}</div>`,
      `<div><strong>Timezone ID:</strong> ${info.timezoneId}</div>`,
      `<div><strong>GMT offset:</strong> ${info.gmtOffset}</div>`,
    ];
    el.timeInfo.innerHTML = lines.join('');
  }

  function setMapView(lat, lng, label) {
    map.setView([lat, lng], 8);
    markers.clearLayers();
    const m = L.marker([lat, lng]).addTo(markers);
    if (label) m.bindPopup(label).openPopup();
  }

  // API Fetch Helpers
  async function fetchNearby(lat, lng) {
    const nearby = await getJSON('./php/geonames.php', {
      endpoint: 'findNearbyPlaceNameJSON',
      lat, lng, radius: 20, cities: 'cities15000', style: 'full', maxRows: 1
    });
    const item = nearby?.geonames?.[0];
    if (!item) return null;
    return {
      name: item.name,
      countryName: item.countryName,
      distance: item.distance ? parseFloat(item.distance) : null,
      population: item.population
    };
  }

  async function fetchTimezone(lat, lng) {
    const tz = await getJSON('./php/geonames.php', { endpoint: 'timezoneJSON', lat, lng });
    if (!tz || tz.status) return null;
    return { timezoneId: tz.timezoneId, gmtOffset: tz.gmtOffset, localTime: tz.time };
  }

  async function fetchWeather(lat, lng) {
    const w = await getJSON('./php/weather.php', { lat, lon: lng });
    if (!w || w.cod !== 200) return null;
    const cur = w.main || {};
    const weather = w.weather && w.weather[0] ? w.weather[0] : {};
    return {
      temp: cur.temp ?? '—',
      humidity: cur.humidity ?? '—',
      description: weather.description ?? '—',
      wind: w.wind?.speed ?? null
    };
  }

  async function fetchCountryInfo(code) {
    if (!code) return null;
    const res = await getJSON('./php/restcountries.php', { code });
    const data = Array.isArray(res) ? res[0] : res;
    if (!data) return null;
    return {
      name: data.name?.common,
      capital: data.capital?.[0],
      population: data.population?.toLocaleString(),
      currency: Object.values(data.currencies ?? {})[0]?.name,
      flag: data.flags?.svg || data.flags?.png,
      languages: data.languages ? Object.values(data.languages).join(', ') : ''
    };
  }

  function updateCountryInfo(country) {
    const elCountry = document.getElementById('countryInfo');
    if (!elCountry) return;
    if (!country) { elCountry.innerHTML = '<em>No country info.</em>'; return; }
    elCountry.innerHTML = `
      <div class="text-center mb-2"><img src="${country.flag}" alt="Flag" width="80" class="border rounded"></div>
      <div><strong>Country:</strong> ${country.name}</div>
      <div><strong>Capital:</strong> ${country.capital}</div>
      <div><strong>Population:</strong> ${country.population}</div>
      <div><strong>Currency:</strong> ${country.currency}</div>
      <div><strong>Languages:</strong> ${country.languages}</div>
    `;
  }

  //  Handle Place Search
  /*async function handlePlaceSearch(query) {
    try {
      if (!query) throw new Error('Please enter a place name.');
      showLoader(true);

      const geo = await getJSON('./php/opencage.php', { q: query, limit: 1 });
      const best = geo?.results?.[0];
      if (!best) throw new Error('No results for that place.');

      const lat = best.geometry.lat, lng = best.geometry.lng;
      const displayName = best.formatted;
      const countryCode = (best.components || {}).country_code?.toUpperCase?.();

      setMapView(lat, lng, displayName);
      const nearby = await fetchNearby(lat, lng);
      const tz = await fetchTimezone(lat, lng);
      const weather = await fetchWeather(lat, lng);

      updatePlaceInfo({ lat, lng, displayName, nearby, countryCode });
      updateTimeInfo(tz);
      updateWeatherInfo(weather);

      // ✅ NEW: Fetch and show country info
      if (countryCode) {
        const country = await fetchCountryInfo(countryCode);
        updateCountryInfo(country);
      }

      saveHistory({ name: displayName, lat, lng });
      notify('Location loaded.');
    } catch (err) {
      console.error(err);
      notify(err.message || 'Search failed.');
    } finally {
      showLoader(false);
    }
  } */
  
  // Handle Place Search
async function handlePlaceSearch(query) {
  try {
    if (!query) throw new Error('Please enter a place name.');
    showLoader(true);

    const geo = await getJSON('./php/opencage.php', { q: query, limit: 1 });
    const best = geo?.results?.[0];
    if (!best) throw new Error('No results for that place.');

    const lat = best.geometry.lat, lng = best.geometry.lng;
    const displayName = best.formatted;
    const countryCode =
      best.components?.country_code?.toUpperCase?.() ||
      best.components?.country?.toUpperCase?.(); //  Fixed fallback

    console.log("Country code detected:", countryCode);

    setMapView(lat, lng, displayName);
    const nearby = await fetchNearby(lat, lng);
    const tz = await fetchTimezone(lat, lng);
    const weather = await fetchWeather(lat, lng);

    updatePlaceInfo({ lat, lng, displayName, nearby, countryCode });
    updateTimeInfo(tz);
    updateWeatherInfo(weather);

    //  Fetch and show country info
    if (countryCode) {
      const country = await fetchCountryInfo(countryCode);
      updateCountryInfo(country);
    } else {
      updateCountryInfo(null);
    }

    saveHistory({ name: displayName, lat, lng });
    notify('Location loaded.');
  } catch (err) {
    console.error(err);
    notify(err.message || 'Search failed.');
  } finally {
    showLoader(false);
  }
}
  


  //  Handle Coordinate Search
  async function handleCoordSearch(lat, lng) {
    try {
      if (typeof lat === 'string') lat = parseFloat(lat);
      if (typeof lng === 'string') lng = parseFloat(lng);
      if (!Number.isFinite(lat) || !Number.isFinite(lng)) throw new Error('Enter valid coordinates.');

      showLoader(true);
      setMapView(lat, lng, `Lat: ${lat.toFixed(5)}, Lng: ${lng.toFixed(5)}`);

      const nearby = await fetchNearby(lat, lng);
      const tz = await fetchTimezone(lat, lng);
      const weather = await fetchWeather(lat, lng);

      updatePlaceInfo({ lat, lng, nearby });
      updateTimeInfo(tz);
      updateWeatherInfo(weather);

      saveHistory({ lat, lng });
      notify('Coordinates loaded.');
    } catch (err) {
      console.error(err);
      notify(err.message || 'Coordinate search failed.');
    } finally {
      showLoader(false);
    }
  }

  //  Event Bindings
  el.form.addEventListener('submit', (e) => { e.preventDefault(); handlePlaceSearch(el.placeInput.value.trim()); });
  el.btnSearchCoords.addEventListener('click', () => handleCoordSearch(el.latInput.value, el.lngInput.value));
  el.btnUseMyLocation.addEventListener('click', () => {
    if (!navigator.geolocation) return notify('Geolocation not supported.');
    navigator.geolocation.getCurrentPosition(
      (pos) => handleCoordSearch(pos.coords.latitude, pos.coords.longitude),
      () => notify('Could not get your location.')
    );
  });
  el.btnClear.addEventListener('click', () => {
    el.placeInput.value = el.latInput.value = el.lngInput.value = '';
    el.placeInfo.innerHTML = '<em>No location selected.</em>';
    el.weatherInfo.innerHTML = '<em>No weather yet.</em>';
    el.timeInfo.innerHTML = '<em>No time/zone yet.</em>';
    document.getElementById('countryInfo').innerHTML = '<em>No country info.</em>';
    markers.clearLayers();
  });

  // Initialize
  map.setView([20, 0], 2);
  renderHistory();

  // Detect user location
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(async (pos) => {
      const { latitude: lat, longitude: lng } = pos.coords;
      try {
        const nearby = await fetchNearby(lat, lng);
        el.userBadge.textContent = nearby?.countryName || 'Detected';
        el.userBadge.title = nearby ? `Nearest: ${nearby.name}, ${nearby.countryName}` : 'Detected by geolocation';
      } catch {
        el.userBadge.textContent = 'Detected';
      }
    }, () => { el.userBadge.textContent = 'Location unavailable'; });
  } else {
    el.userBadge.textContent = 'Geolocation unsupported';
  }
})();
