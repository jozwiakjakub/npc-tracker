<?php 
include("auth.php"); 

// Wczytanie changelogu z pliku changelog.txt
$changelogContent = '';
$changelogFile = __DIR__ . '/changelog.txt';
if (file_exists($changelogFile)) {
  // Nie używamy htmlspecialchars, bo plik zawiera już tagi HTML
  $changelogContent = file_get_contents($changelogFile);
} else {
  $changelogContent = '<li>Brak changelogu.</li>';
}
?>

<script>
  const CURRENT_USER = <?= json_encode($username) ?>;
</script>
<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <title>Ukryty diler by dzwq</title>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <link rel="stylesheet" href="css/style.css">
  <style>
    #imgModal {
      display: none;
      position: fixed;
      z-index: 10000;
      left: 0; top: 0;
      width: 100%; height: 100%;
      background: rgba(0,0,0,0.8);
      justify-content: center;
      align-items: center;
    }
    #imgModal img {
      max-width: 90%;
      max-height: 90%;
      border-radius: 10px;
    }
    #closeModal {
      position: absolute;
      top: 20px; right: 30px;
      color: #fff;
      font-size: 40px;
      cursor: pointer;
      user-select: none;
    }
    #changelogTooltip::-webkit-scrollbar {
      width: 6px;
    }
    #changelogTooltip::-webkit-scrollbar-track {
      background: transparent;
    }
    #changelogTooltip::-webkit-scrollbar-thumb {
      background-color: #bbb;
      border-radius: 10px;
    }
    /* Dodane style dla historii */
    #historyTooltip::-webkit-scrollbar {
      width: 6px;
    }
    #historyTooltip::-webkit-scrollbar-track {
      background: transparent;
    }
    #historyTooltip::-webkit-scrollbar-thumb {
      background-color: #bbb;
      border-radius: 10px;
    }
  </style>
</head>

<body>

<!-- Przycisk: Aktywne sesje -->
<button id="activeSessionsBtn" style="
  position: fixed;
  bottom: 10px;
  left: 110px;
  padding: 10px 8px;
  background: #4a90e2;
  color: #fff;
  font-weight: bold;
  border: none;
  border-radius: 10px;
  font-family: Arial, sans-serif;
  font-size: 14px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.3);
  cursor: pointer;
  z-index: 9999;
  transition: background 0.2s;
">Aktywne sesje</button>

<!-- Pudełko sesji -->
<div id="activeSessionsBox" style="
  display: none;
  position: fixed;
  bottom: 60px;
  left: 15px;
  background: #fff;
  border: 1px solid #ccc;
  padding: 12px;
  max-height: 240px;
  max-width: 320px;
  overflow-y: auto;
  box-shadow: 0 3px 10px rgba(0,0,0,0.3);
  border-radius: 12px;
  font-family: Arial, sans-serif;
  font-size: 13px;
  line-height: 1.4;
  z-index: 9999;
"></div>

<!-- Przycisk: Wyloguj -->
<a href="logout.php" style="
  position: fixed;
  bottom: 10px;
  right: 10px;
  background-color: #d9534f;
  color: white;
  padding: 10px 16px;
  border-radius: 10px;
  text-decoration: none;
  font-family: Arial, sans-serif;
  font-weight: bold;
  font-size: 14px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.3);
  z-index: 11000;
  transition: background 0.2s;
">Wyloguj się</a>

<!-- Przycisk: Changelog -->
<a href="#" id="changelogBtn" style="
  position: fixed;
  bottom: 10px;
  right: 140px;
  background-color: #0275d8;
  color: white;
  padding: 10px 16px;
  border-radius: 10px;
  text-decoration: none;
  font-family: Arial, sans-serif;
  font-weight: bold;
  font-size: 14px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.3);
  z-index: 11000;
  transition: background 0.2s;
">Changelog</a>

<!-- Przycisk: Historia -->
<a href="#" id="historyBtn" style="
  position: fixed;
  bottom: 10px;
  left: 10px;
  background-color: #5bc0de;
  color: white;
  padding: 10px 16px;
  border-radius: 10px;
  text-decoration: none;
  font-family: Arial, sans-serif;
  font-weight: bold;
  font-size: 14px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.3);
  z-index: 11000;
  transition: background 0.2s;
">Historia</a>

<!-- Tooltip: Historia -->
<div id="historyTooltip" style="
  display: none;
  position: fixed;
  bottom: 50px;
  left: 10px;
  width: 400px;
  max-height: 300px;
  overflow-y: auto;
  background: #fff;
  color: #333;
  padding: 16px;
  border-radius: 10px;
  box-shadow: 0 3px 10px rgba(0,0,0,0.3);
  font-family: Arial, sans-serif;
  font-size: 13px;
  font-weight: 600;
  line-height: 1.5;
  white-space: nowrap;
  z-index: 12000;
  scrollbar-width: thin;
  scrollbar-color: #bbb transparent;
"></div>

<!-- Tooltip: Changelog -->
<div id="changelogTooltip" style="
  display: none;
  position: fixed;
  bottom: 60px;
  right: 10px;
  width: 400px;
  height: 160px;
  overflow-y: auto;
  background: #fff;
  color: #333;
  padding: 16px;
  border-radius: 10px;
  box-shadow: 0 3px 10px rgba(0,0,0,0.3);
  font-family: Arial, sans-serif;
  font-size: 13px;
  font-weight: 600;
  line-height: 1.5;
  white-space: normal;
  z-index: 12000;
  scrollbar-width: thin;
  scrollbar-color: #bbb transparent;
"></div>


<div id="dilers-container" style="position: fixed; top: 10px; left: 10px; z-index: 11000; max-width: 300px;"></div>
<div id="active-dilers-container" style="
  position: fixed;
  top: 10px;
  left: 10px;
  z-index: 11000;
  max-width: 300px;
  font-family: Arial, sans-serif;
"></div>
<div id="search-box">
  <input type="text" id="searchInput" placeholder="Szukaj miejsca...">
  <ul id="autocomplete-list"></ul>
</div>

<div id="znajdzki-counter">Znajdźki: 0</div>

<div id="legenda-box">
  <button id="toggleLegenda">Legenda</button>
  <ul id="legenda-list" style="display: none;"></ul>
</div>

<div id="map"></div>

<div id="imgModal">
  <span id="closeModal">&times;</span>
  <img id="modalImg" src="" alt="Powiększone zdjęcie" />
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="js/data.js"></script>
<script src="js/petrol.js"></script>
<script src="js/script.js"></script>

<script>
  const changelogText = `<?= $changelogContent ?>`;
  const changelogBtn = document.getElementById('changelogBtn');
  const changelogTooltip = document.getElementById('changelogTooltip');

  changelogBtn.addEventListener('click', (e) => {
    e.preventDefault();
    if (changelogTooltip.style.display === 'none' || changelogTooltip.style.display === '') {
      changelogTooltip.innerHTML = `<ul style="margin: 0; padding-left: 20px;">${changelogText}</ul>`;
      changelogTooltip.style.display = 'block';
    } else {
      changelogTooltip.style.display = 'none';
    }
  });

  document.addEventListener('click', (e) => {
    if (!changelogTooltip.contains(e.target) && e.target !== changelogBtn) {
      changelogTooltip.style.display = 'none';
    }
  });

  // Skrypt dla przycisku Historia i okna historii
  const historyBtn = document.getElementById('historyBtn');
  const historyTooltip = document.getElementById('historyTooltip');

  historyBtn.addEventListener('click', (e) => {
    e.preventDefault();
    if (historyTooltip.style.display === 'none' || historyTooltip.style.display === '') {
      fetch('get_loc_history.php')
        .then(res => res.json())
        .then(data => {
          if (data.length === 0) {
            historyTooltip.innerHTML = '<p>Brak historii.</p>';
          } else {
            const html = data.map(entry =>
              `<div><strong>${entry.username}</strong>;${entry.marker};${entry.value}; <small>${entry.timestamp}</small></div>`
            ).join('');
            historyTooltip.innerHTML = html;
          }
          historyTooltip.style.display = 'block';
        });
    } else {
      historyTooltip.style.display = 'none';
    }
  });

  document.addEventListener('click', (e) => {
    if (!historyTooltip.contains(e.target) && e.target !== historyBtn) {
      historyTooltip.style.display = 'none';
    }
  });

  // Co 10 sekund pinguj serwer, by utrzymać aktywność sesji
  setInterval(() => {
    fetch('ping.php');
  }, 10000);

  // Gdy użytkownik zamknie kartę — wyloguj natychmiast
  window.addEventListener('unload', () => {
    navigator.sendBeacon('unload_ping.php');
  });

  // Aktywne sesje — przycisk i okno
  const btn = document.getElementById('activeSessionsBtn');
  const box = document.getElementById('activeSessionsBox');

  function formatInactive(seconds) {
    if (seconds < 60) return `${seconds} sek temu`;
    const m = Math.floor(seconds / 60);
    const s = seconds % 60;
    return s === 0 ? `${m} min temu` : `${m} min ${s} sek temu`;
  }

  if (btn && box) {
    btn.addEventListener('click', () => {
      box.style.display = box.style.display === 'none' ? 'block' : 'none';
      if (box.style.display === 'block') loadSessions();
    });

    function loadSessions() {
      fetch('sessions.php')
        .then(res => res.json())
        .then(data => {
          if (!Array.isArray(data)) return;

          if (data.length === 0) {
            box.innerHTML = '<b>Aktywne sesje:</b><br><i>Brak aktywnych użytkowników.</i>';
            return;
          }

          box.innerHTML = '<b>Aktywne sesje:</b><ul style="padding-left: 20px;">' +
            data.map(d => {
              const timeAgo = formatInactive(d.inactive_time);
              return `<li>${d.username} (SID: ${d.sid})<br><small>${timeAgo}</small></li>`;
            }).join('') +
            '</ul>';
        });
    }

    setInterval(() => {
      if (box.style.display === 'block') loadSessions();
    }, 30000);
  }
</script>

</body>
</html>
