<?php
$wm = "T2CSW";
$url = "https://chaturbate.com/api/public/affiliates/onlinerooms/?wm=$wm&client_ip=request_ip&format=json&limit=500";

$json = @file_get_contents($url);
$rooms = $json ? json_decode($json, true)['rooms'] ?? [] : [];
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Live Cams • T2CSW 20% Revshare</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    body { font-family: system-ui, sans-serif; }
    .card { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
    .card:hover { transform: scale(1.05); box-shadow: 0 0 30px rgba(236, 72, 153, 0.6); }
    .modal iframe { border: none; }
    .tag-btn { transition: all 0.2s; }
  </style>
</head>
<body class="bg-zinc-950 text-white min-h-screen">
  <div class="max-w-7xl mx-auto p-6">
    <!-- HEADER -->
    <div class="flex flex-col md:flex-row items-center justify-between mb-8 gap-4">
      <div class="flex items-center gap-4">
        <h1 class="text-5xl font-black tracking-tighter text-pink-500">LIVE<span class="text-white">CAMS</span></h1>
        <div class="text-xs px-3 py-1 bg-green-500/10 text-green-400 rounded-full font-mono">20% LIFETIME REVSHARE • T2CSW</div>
      </div>
      <div class="flex items-center gap-6 text-sm">
        <div class="text-center">
          <div class="text-2xl font-bold text-green-400" id="total-models">0</div>
          <div class="text-zinc-500 text-xs">MODELS ONLINE</div>
        </div>
        <div class="text-center">
          <div class="text-2xl font-bold text-green-400" id="total-watchers">0</div>
          <div class="text-zinc-500 text-xs">WATCHING NOW</div>
        </div>
      </div>
      <button onclick="toggleDarkMode()" id="theme-toggle"
              class="flex items-center gap-2 px-5 py-2 bg-zinc-800 hover:bg-zinc-700 rounded-2xl text-sm font-medium">
        <span id="theme-icon">🌙</span> Dark Mode
      </button>
    </div>

    <!-- SEARCH + SORT -->
    <div class="flex flex-col md:flex-row gap-4 mb-8">
      <input id="search-input" type="text" placeholder="🔍 Search username or show title..."
             class="flex-1 bg-zinc-900 border border-zinc-700 focus:border-pink-500 rounded-3xl px-6 py-4 text-lg outline-none"
             oninput="handleSearch()">
      <select id="sort-select" onchange="handleSort()" class="bg-zinc-900 border border-zinc-700 rounded-3xl px-6 py-4 text-lg outline-none">
        <option value="viewers">Most Popular (Viewers)</option>
        <option value="name">A–Z Username</option>
      </select>
    </div>

    <!-- GENDER FILTERS -->
    <div class="flex flex-wrap gap-3 justify-center mb-6">
      <button onclick="setGenderFilter('')" id="gender-all" class="active-filter px-7 py-3 bg-pink-600 hover:bg-pink-500 rounded-3xl text-sm font-semibold">ALL</button>
      <button onclick="setGenderFilter('f')" id="gender-f" class="px-7 py-3 bg-zinc-800 hover:bg-zinc-700 rounded-3xl text-sm font-semibold">♀ Female</button>
      <button onclick="setGenderFilter('m')" id="gender-m" class="px-7 py-3 bg-zinc-800 hover:bg-zinc-700 rounded-3xl text-sm font-semibold">♂ Male</button>
      <button onclick="setGenderFilter('c')" id="gender-c" class="px-7 py-3 bg-zinc-800 hover:bg-zinc-700 rounded-3xl text-sm font-semibold">👫 Couple</button>
      <button onclick="setGenderFilter('t')" id="gender-t" class="px-7 py-3 bg-zinc-800 hover:bg-zinc-700 rounded-3xl text-sm font-semibold">⚧ Trans</button>
    </div>

    <!-- TAGS -->
    <div id="tags-cloud" class="flex flex-wrap gap-2 justify-center mb-10"></div>

    <!-- GRID -->
    <div id="grid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 2xl:grid-cols-7 gap-6"></div>
  </div>

  <!-- LIVE EMBED MODAL -->
  <div id="modal" class="hidden fixed inset-0 bg-black/95 z-50 flex items-center justify-center p-4">
    <div class="bg-zinc-900 rounded-3xl max-w-6xl w-full overflow-hidden">
      <div class="flex items-center justify-between px-6 py-4 border-b border-zinc-700">
        <div id="modal-title" class="font-bold text-xl"></div>
        <button onclick="closeModal()" class="text-4xl leading-none text-zinc-400 hover:text-white">×</button>
      </div>
      <iframe id="embed-iframe" class="w-full aspect-video" allowfullscreen allow="camera; microphone"></iframe>
    </div>
  </div>

  <script>
    let allRooms = <?php echo json_encode($rooms); ?>;
    let currentGender = "";
    let currentTag = "";
    let searchTerm = "";
    let sortMode = "viewers";

    function updateStats() {
      document.getElementById("total-models").textContent = allRooms.length;
      const watchers = allRooms.reduce((sum, r) => sum + (r.num_users || 0), 0);
      document.getElementById("total-watchers").textContent = watchers.toLocaleString();
    }

    function updateTagsCloud() {
      const container = document.getElementById("tags-cloud");
      container.innerHTML = `<button onclick="setTagFilter('')" class="tag-btn px-5 py-2 bg-zinc-800 hover:bg-pink-600 rounded-3xl text-xs font-medium">ALL TAGS</button>`;
      const tagCounts = {};
      allRooms.forEach(room => (room.tags || []).forEach(t => tagCounts[t] = (tagCounts[t] || 0) + 1));
      Object.entries(tagCounts).sort((a,b)=>b[1]-a[1]).slice(0,15).forEach(([tag,count]) => {
        const btn = document.createElement("button");
        btn.className = `tag-btn px-5 py-2 rounded-3xl text-xs font-medium ${currentTag===tag?"bg-pink-600":"bg-zinc-800 hover:bg-zinc-700"}`;
        btn.textContent = `${tag} (${count})`;
        btn.onclick = () => setTagFilter(tag);
        container.appendChild(btn);
      });
    }

    function applyFiltersAndSort() {
      let filtered = allRooms.filter(room => {
        if (currentGender && room.gender !== currentGender) return false;
        if (currentTag && !(room.tags || []).includes(currentTag)) return false;
        if (searchTerm) {
          const term = searchTerm.toLowerCase();
          return room.username.toLowerCase().includes(term) || (room.room_subject || "").toLowerCase().includes(term);
        }
        return true;
      });

      if (sortMode === "viewers") filtered.sort((a,b) => (b.num_users||0) - (a.num_users||0));
      else filtered.sort((a,b) => a.username.localeCompare(b.username));

      renderGrid(filtered);
    }

    function renderGrid(rooms) {
      const grid = document.getElementById("grid");
      grid.innerHTML = rooms.length ? "" : `<div class="col-span-full text-center py-20 text-xl">No cams match your filters 😢</div>`;
      rooms.forEach(room => {
        const card = document.createElement("div");
        card.className = "card bg-zinc-900 rounded-3xl overflow-hidden border border-zinc-800";
        card.innerHTML = `
          <a href="${room.chat_room_url_revshare}" target="_blank" class="block relative">
            <img src="${room.image_url_360x270 || room.image_url}" class="w-full aspect-video object-cover" alt="${room.username}">
            <div class="absolute top-3 right-3 bg-black/70 text-xs px-3 py-1 rounded-full font-mono">${room.num_users || 0}</div>
          </a>
          <div class="p-4">
            <div class="font-bold text-xl">${room.username}</div>
            <div class="text-zinc-400 text-sm line-clamp-2">${room.room_subject || "No description"}</div>
            <div class="flex gap-3 mt-5">
              <a href="${room.chat_room_url_revshare}" target="_blank" class="flex-1 text-center py-3 bg-green-600 hover:bg-green-500 rounded-2xl font-semibold text-sm">💬 CHAT</a>
              <button onclick="openModalEmbed('${room.iframe_embed_revshare}', '${room.username}')" class="flex-1 text-center py-3 bg-pink-600 hover:bg-pink-500 rounded-2xl font-semibold text-sm">📺 WATCH LIVE</button>
            </div>
          </div>`;
        grid.appendChild(card);
      });
    }

    function setGenderFilter(g) { currentGender = g; updateActiveGenderButtons(); applyFiltersAndSort(); }
    function updateActiveGenderButtons() {
      ["all","f","m","c","t"].forEach(id => {
        const btn = document.getElementById("gender-"+id);
        btn.classList.toggle("!bg-pink-600", id === (currentGender || "all"));
      });
    }
    function setTagFilter(t) { currentTag = t || ""; updateTagsCloud(); applyFiltersAndSort(); }
    function handleSearch() { searchTerm = document.getElementById("search-input").value.trim(); applyFiltersAndSort(); }
    function handleSort() { sortMode = document.getElementById("sort-select").value; applyFiltersAndSort(); }

    function openModalEmbed(url, name) {
      document.getElementById("modal-title").innerHTML = `${name} <span class="text-green-400 text-sm font-normal">● LIVE</span>`;
      document.getElementById("embed-iframe").src = url;
      document.getElementById("modal").classList.remove("hidden");
      document.body.style.overflow = "hidden";
    }
    function closeModal() {
      document.getElementById("modal").classList.add("hidden");
      document.getElementById("embed-iframe").src = "";
      document.body.style.overflow = "visible";
    }
    function toggleDarkMode() {
      document.documentElement.classList.toggle("dark");
      document.getElementById("theme-icon").textContent = document.documentElement.classList.contains("dark") ? "🌙" : "☀️";
    }

    document.addEventListener("keydown", e => { if (e.key === "Escape") closeModal(); });

    // INIT
    window.onload = () => {
      document.documentElement.classList.add("dark");
      updateStats();
      updateTagsCloud();
      applyFiltersAndSort();
      // Auto-refresh every 90 seconds (pulls fresh data from Chaturbate)
      setInterval(() => location.reload(), 90000);
    };
  </script>
</body>
</html>
