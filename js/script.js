const map = L.map('map', {
  crs: L.CRS.Simple,
  minZoom: -0.5,
  maxZoom: 4
});

const imageUrl = 'img/mapa.jpg';
const imageBounds = [[0, 0], [1000, 1000]];
L.imageOverlay(imageUrl, imageBounds).addTo(map);
map.fitBounds(imageBounds);

const znajdzkiCount = markers.filter(m => m.category === "znajdzka").length;
document.getElementById("znajdzki-counter").innerText = `Dilerów: ${znajdzkiCount}`;

const markerMap = {};
const COLOR_DEFAULT = '#FFA500';
const COLOR_NO = '#d9534f';
const COLOR_YES = '#5cb85c';

// Tworzenie markerów dla NPC i znajdziek
markers.forEach(m => {
  const marker = L.circleMarker([m.lat, m.lng], {
    radius: 8,
    color: COLOR_DEFAULT,
    fillColor: COLOR_DEFAULT,
    fillOpacity: 0.8
  }).addTo(map);

  let popupHtml = `<b>${m.name}</b>`;

  let buttons = [];
  if (m.category === "npc") {
    buttons = [
      { label: "??", color: "green" },
      { label: "1500", color: "green" },
      { label: "1750", color: "green" },
      { label: "nie ma", color: "red" }
    ];
  } else if (m.category === "znajdzka") {
    buttons = [
      { label: "??", color: "green" },
      { label: "1850", color: "green" },
      { label: "2100", color: "green" },
      { label: "2220", color: "green" },
      { label: "2520", color: "green" },
      { label: "nie ma", color: "red" }
    ];
  }

  if (buttons.length > 0) {
    popupHtml += `<div style="margin: 5px 0;">`;
    buttons.forEach(btn => {
      popupHtml += `<button class="btn-click" style="
        background-color: ${btn.color};
        color: white;
        border: none;
        border-radius: 4px;
        padding: 4px 8px;
        margin-right: 5px;
        cursor: pointer;
        font-weight: bold;
      " data-value="${btn.label}">${btn.label}</button>`;
    });
    popupHtml += `</div>`;
  }

  if (m.img) {
    popupHtml += `<img src="${m.img}" alt="zdjęcie" style="max-width: 250px; height: auto; margin-top: 5px; border-radius: 4px; cursor: pointer;">`;
  }

  marker.bindPopup(popupHtml);
  markerMap[m.name] = marker;
});

function fetchAndUpdate() {
  fetch('get_loc_story.php')
    .then(res => res.json())
    .then(data => {
      const now = new Date();
      const latestByMarker = {};

      data.forEach(entry => {
        const markerName = entry.marker;
        const entryTime = new Date(entry.timestamp);
        const minutesSince = (now - entryTime) / 60000;

        // Pomiń zgłoszenia starsze niż 35 minut
        if (minutesSince > 35) return;

        if (!latestByMarker[markerName] || entryTime > new Date(latestByMarker[markerName].timestamp)) {
          latestByMarker[markerName] = entry;
        }
      });

      markers.forEach(m => {
        const marker = markerMap[m.name];
        if (!marker) return;

        let color = COLOR_DEFAULT;
        const lastEntry = latestByMarker[m.name];

        if (lastEntry) {
          const lastTimestamp = new Date(lastEntry.timestamp);
          const diffMinutes = (now - lastTimestamp) / 60000;

          if (lastEntry.value === 'nie ma' && diffMinutes <= 8) {
            color = COLOR_NO;
          } else if (lastEntry.value !== 'nie ma' && diffMinutes <= 35) {
            color = COLOR_YES;
          }
        }

        marker.setStyle({ color: color, fillColor: color });
      });

      const container = document.getElementById('active-dilers-container');
      container.innerHTML = '';

      data.forEach(entry => {
        if (entry.value === 'nie ma') return;

        const entryTime = new Date(entry.timestamp);
        const minutesSince = (now - entryTime) / 60000;
        if (minutesSince > 45) return;

        const div = document.createElement('div');
        div.style.cssText = `
          display: flex; justify-content: space-between; align-items: center;
          background: #f0f0f0; border: 1px solid #aaa; border-radius: 6px;
          padding: 8px 12px; margin-bottom: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.2); gap: 12px;
        `;

        const zgłoszonoStr = entryTime.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

        const info = document.createElement('div');
        info.style.flexGrow = '1';
        info.innerHTML = `
          <strong>Szef:</strong> ${entry.username}<br>
          <strong>Diler:</strong> ${entry.marker}<br>
          <strong>Wartość:</strong> ${entry.value}<br>
          <small>Zgłoszono o: ${zgłoszonoStr}</small>
        `;

        const btn = document.createElement('button');
        btn.className = 'btn-nie-mam';
        btn.textContent = 'nie ma';
        btn.style.cssText = `
          background: #d9534f; border: none; color: white; border-radius: 4px;
          cursor: pointer; font-weight: bold; padding: 2px 6px; font-size: 12px;
        `;

        btn.addEventListener('click', () => {
          const timestamp = new Date().toISOString();

          fetch('save_click.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              username: CURRENT_USER,
              marker: entry.marker,
              value: 'nie ma',
              timestamp: timestamp
            })
          })
            .then(res => res.text())
            .then(() => {
              container.removeChild(div);
              fetchAndUpdate();
            });
        });

        div.appendChild(info);
        div.appendChild(btn);
        container.appendChild(div);
      });
    });
}


fetchAndUpdate();
setInterval(fetchAndUpdate, 30000);

// Obsługa kliknięcia na popupy markerów NPC i znajdziek
map.on('popupopen', function (e) {
  const popupNode = e.popup.getElement();
  const content = e.popup.getContent();

  if (typeof content === 'string') {
    const match = content.match(/<b>(.*?)<\/b>/);
    const markerName = match ? match[1] : null;

    if (!markerName) return;

    const buttons = popupNode.querySelectorAll('.btn-click');
    buttons.forEach(button => {
      button.addEventListener('click', () => {
        const val = button.getAttribute('data-value');
        const timestamp = new Date().toISOString();

        fetch('save_click.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            username: CURRENT_USER,
            marker: markerName,
            value: val,
            timestamp: timestamp
          })
        })
          .then(res => res.text())
          .then(() => {
            fetchAndUpdate();
          });
      });
    });

    const img = popupNode.querySelector('img');
    if (img) {
      img.style.cursor = 'pointer';
      img.onclick = () => {
        const modal = document.getElementById('imgModal');
        const modalImg = document.getElementById('modalImg');
        modal.style.display = 'flex';
        modalImg.src = img.src;
      };
    }
  }
});

document.addEventListener('DOMContentLoaded', () => {
  document.getElementById('closeModal').addEventListener('click', () => {
    document.getElementById('imgModal').style.display = 'none';
  });

  document.getElementById('imgModal').addEventListener('click', (e) => {
    if (e.target.id === 'imgModal') {
      e.target.style.display = 'none';
    }
  });
});

// Obsługa legendy i wyszukiwarki
document.getElementById("toggleLegenda").addEventListener("click", () => {
  const list = document.getElementById("legenda-list");
  list.style.display = list.style.display === "none" ? "block" : "none";
});

const legendaList = document.getElementById("legenda-list");
markers.forEach(m => {
  const li = document.createElement("li");
  li.textContent = m.name;
  li.addEventListener("click", () => {
    const marker = markerMap[m.name];
    if (marker) {
      map.setView(marker.getLatLng(), 2);
      marker.openPopup();
    }
  });
  legendaList.appendChild(li);
});

const searchInput = document.getElementById("searchInput");
const autocompleteList = document.getElementById("autocomplete-list");

searchInput.addEventListener("input", () => {
  const query = searchInput.value.toLowerCase();
  autocompleteList.innerHTML = "";

  if (!query) {
    autocompleteList.style.display = "none";
    return;
  }

  const matches = markers.filter(m => m.name.toLowerCase().includes(query));

  matches.forEach(m => {
    const li = document.createElement("li");
    li.textContent = m.name;
    li.addEventListener("click", () => {
      const marker = markerMap[m.name];
      if (marker) {
        map.setView(marker.getLatLng(), 2);
        marker.openPopup();
        autocompleteList.style.display = "none";
        searchInput.value = m.name;
      }
    });
    autocompleteList.appendChild(li);
  });

  autocompleteList.style.display = "block";
});

searchInput.addEventListener("keydown", (e) => {
  if ((e.key === "Enter" || e.key === "Tab") && autocompleteList.style.display !== "none") {
    e.preventDefault();
    const firstItem = autocompleteList.querySelector("li");
    if (firstItem) {
      firstItem.click();
    }
  }
});

document.addEventListener("click", (e) => {
  if (!document.getElementById("search-box").contains(e.target)) {
    autocompleteList.style.display = "none";
  }
});

// Reklamy
function parseAdTags(text) {
  return text
    .replace(/\[b\](.*?)\[\/b\]/g, '<strong>$1</strong>')
    .replace(/\[red\](.*?)\[\/red\]/g, '<span style="color:red;">$1</span>')
    .replace(/\[blue\](.*?)\[\/blue\]/g, '<span style="color:blue;">$1</span>')
    .replace(/\[green\](.*?)\[\/green\]/g, '<span style="color:green;">$1</span>')
    .replace(/\[gray\](.*?)\[\/gray\]/g, '<span style="color:gray;">$1</span>');
}

fetch('ad.txt')
  .then(res => res.ok ? res.text() : Promise.reject())
  .then(text => {
    const banner = document.getElementById('ad-banner');
    const ads = text.trim().split('\n').map(parseAdTags);
    banner.innerHTML = `
      <div class="banner-prefix">dzwq-news</div>
      <div class="banner-content">${ads.join('<hr style="border:none;border-top:1px dashed #aaa;margin:6px 0;">')}</div>
    `;
  })
  .catch(() => console.warn('Brak reklamy'));

// STACJE PALIW
fetch('get_petrol_data.php')
  .then(res => res.json())
  .then(petrolData => {
    petrolMarkers.forEach(p => {
      const petrolInfo = petrolData[p.name] || {};
      const value = petrolInfo.value;
      const username = petrolInfo.username || 'nieznany';
      const hasValue = typeof value === 'number' && !isNaN(value);

      function getColorForValue(val) {
        if (val <= 3) return '#00cc00';
        if (val >= 9.99) return '#cc0000';
        if (val === 5) return '#ffa500';

        if (val < 5) {
          const ratio = (val - 3) / (5 - 3);
          return interpolateColor('#00cc00', '#ffa500', ratio);
        } else {
          const ratio = (val - 5) / (9.99 - 5);
          return interpolateColor('#ffa500', '#cc0000', ratio);
        }
      }

      function interpolateColor(color1, color2, factor) {
        const c1 = hexToRgb(color1);
        const c2 = hexToRgb(color2);
        const r = Math.round(c1.r + (c2.r - c1.r) * factor);
        const g = Math.round(c1.g + (c2.g - c1.g) * factor);
        const b = Math.round(c1.b + (c2.b - c1.b) * factor);
        return `rgb(${r}, ${g}, ${b})`;
      }

      function hexToRgb(hex) {
        const bigint = parseInt(hex.replace('#', ''), 16);
        return {
          r: (bigint >> 16) & 255,
          g: (bigint >> 8) & 255,
          b: bigint & 255
        };
      }

      const priceColor = hasValue ? getColorForValue(value) : '#999';

      const petrolDivIcon = L.divIcon({
        className: 'petrol-icon',
        html: `
          <div style="
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
          ">
            <img src="img/petrol.png" style="width: 16px; height: 16px;" />
            ${hasValue ? `
              <div style="
                position: absolute;
                top: -10px;
                right: -5px;
                background: rgba(255,255,255,0.95);
                color: ${priceColor};
                font-size: 13px;
                font-weight: bold;
                padding: 1px 3px;
                border-radius: 3px;
                border: 1px solid #ccc;
              ">${value.toFixed(2)}</div>` : ''}
          </div>
        `,
        iconSize: [24, 24],
        iconAnchor: [12, 12]
      });

      const marker = L.marker([p.lat, p.lng], { icon: petrolDivIcon }).addTo(map);

      marker.bindPopup(() => {
        const container = document.createElement('div');
        container.style.cssText = `
          font-family: Arial, sans-serif;
          font-size: 13px;
          font-weight: bold;
          max-width: 250px;
          line-height: 1.3;
          display: flex;
          flex-direction: column;
          align-items: center;
          justify-content: center;
          text-align: center;
          height: 160px;
        `;

        const title = document.createElement('div');
        title.style.marginBottom = '6px';
        title.textContent = p.name;

        const current = document.createElement('div');
        current.style.marginBottom = '10px';
        current.innerHTML = `
          Cena: <strong>${hasValue ? value.toFixed(2) : 'Brak danych'}</strong><br>
          <small style="font-weight: normal;">Dodane przez: ${username}</small>
        `;

        const input = document.createElement('input');
        input.type = 'text';
        input.placeholder = 'Nowa cena';
        input.id = `price-input-${p.name}`;
        input.style.cssText = `
          width: 80%;
          padding: 4px;
          margin-bottom: 8px;
          box-sizing: border-box;
          font-size: 13px;
          text-align: center;
        `;

        const btn = document.createElement('button');
        btn.textContent = 'Zapisz';
        btn.style.cssText = `
          background-color: #5cb85c;
          color: white;
          border: none;
          padding: 6px 0;
          width: 80%;
          border-radius: 3px;
          font-weight: bold;
          cursor: pointer;
          font-size: 13px;
          line-height: 1;
        `;

        btn.addEventListener('click', () => {
          let val = input.value.trim().replace(',', '.');
          const numVal = parseFloat(val);

          if (!val || isNaN(numVal)) {
            alert('Wprowadź poprawną liczbę, np. 3.99');
            return;
          }

          if (numVal < 3.00 || numVal > 9.99) {
            alert('Cena musi być w zakresie od 3,00 do 9,99');
            return;
          }

          fetch('save_petrol.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              name: p.name,
              value: numVal,
              username: CURRENT_USER
            })
          })
            .then(res => res.text())
            .then(text => {
              alert(text);
              marker.closePopup();
              location.reload(); // lub odśwież marker
            })
            .catch(() => alert('Nie udało się zapisać.'));
        });

        container.appendChild(title);
        container.appendChild(current);
        container.appendChild(input);
        container.appendChild(btn);
        return container;
      });
    });
  });

// Dla debugowania kliknięć na mapie: wypisanie współrzędnych kliknięcia
map.on('click', function (e) {
  const lat = e.latlng.lat.toFixed(5);
  const lng = e.latlng.lng.toFixed(5);
  console.log(`lat: ${lat},\nlng: ${lng}`);
});

// --------- AKTYWNE SESJE I WYLOGOWANIE PO 35 MINUTACH ---------

// Ping do serwera (odświeża znacznik aktywności)
function sendPing() {
  fetch('ping.php', { method: 'POST' })
    .then(res => res.text())
    .then(text => {
      if (text === 'expired') {
        alert('Twoja sesja wygasła. Nastąpi wylogowanie.');
        window.location.href = 'logout.php';
      }
    })
    .catch(() => {});
}

// Ping natychmiast po wejściu
sendPing();
let lastPing = Date.now();

// Nasłuchiwanie aktywności i pingi co max 30 sekund
const activityEvents = ['click', 'scroll'];

function handleActivity() {
  const now = Date.now();
  if (now - lastPing >= 30000) {
    lastPing = now;
    sendPing();
  }
}
activityEvents.forEach(event => {
  window.addEventListener(event, handleActivity);
});

// Wyślij beacon przy zamknięciu karty/przeglądarki
window.addEventListener('beforeunload', () => {
  navigator.sendBeacon('unload_ping.php');
});

// ---------- PANEL AKTYWNYCH SESJI ----------

function loadSessions() {
  fetch('sessions.php')
    .then(res => res.json())
    .then(users => {
      const box = document.getElementById('sessionsBox');
      if (!box) return;

      box.innerHTML = '';

      if (users.length === 0) {
        box.textContent = 'Brak aktywnych użytkowników.';
        return;
      }

      users.forEach(user => {
        const div = document.createElement('div');
        const seconds = user.inactive_time;
        const minutes = Math.floor(seconds / 60);
        const secs = seconds % 60;

        let timeStr = '';
        if (minutes > 0) {
          timeStr = `${minutes} min ${secs} sek temu`;
        } else {
          timeStr = `${secs} sek temu`;
        }

        div.textContent = `${user.username} (SID: ${user.sid}) — aktywny: ${timeStr}`;
        box.appendChild(div);
      });
    })
    .catch(() => {});
}

if (btn && box) {
  btn.addEventListener('click', () => {
    box.style.display = box.style.display === 'none' ? 'block' : 'none';
    if (box.style.display === 'block') loadSessions();
  });

  // Odświeżaj co 30s, ale tylko jeśli widoczne
  setInterval(() => {
    if (box.style.display === 'block') loadSessions();
  }, 30000);
}
