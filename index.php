<?php
define('ADMIN_USER', 'DelgadoCayo2025');
define('ADMIN_PASS_HASH', '$2a$12$tKRNUZ21ixOahirhLTyamuD0Ma4ODVqRYEal7/gSN5A3DrgEn2nT6');
define('GUARDIA_PASS_HASH', '$2a$12$JBme9Hv/j.3FrFZrNyvKTO9daffzMGnjaFuAFhgVjFStaOLck6H3y'); 


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    header('Content-Type: application/json');

    if (isset($input['action']) && $input['action'] === 'login') {
        $response = ['status' => 'failure'];
        
        if ($input['type'] === 'admin' && isset($input['user'], $input['pass'])) {
            if ($input['user'] === ADMIN_USER && password_verify($input['pass'], ADMIN_PASS_HASH)) {
                $response['status'] = 'success';
            }
        }
        
        // Connexion Guardia
        if ($input['type'] === 'guardia' && isset($input['pass'])) {
            if (password_verify($input['pass'], GUARDIA_PASS_HASH)) {
                $response['status'] = 'success';
            }
        }
        
        echo json_encode($response);
        exit;
    }

    if (isset($input['data'])) {
        $personData = $input['data'];
        $dbFile = 'database.json';
        $database = [];

        if (file_exists($dbFile)) {
            $database = json_decode(file_get_contents($dbFile), true);
            if (!is_array($database)) { $database = []; }
        }

        $personId = $personData['id'];
        $found = false;

        foreach ($database as $key => &$person) {
            if (isset($person['id']) && $person['id'] === $personId) {
                $database[$key] = $personData;
                $found = true;
                break;
            }
        }
        unset($person);

        if (!$found) {
            $database[] = $personData;
        }

        file_put_contents($dbFile, json_encode($database, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        echo json_encode(['status' => 'success', 'message' => 'Données enregistrées.']);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Documents Cayo Perico</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { background: #0a0a0a; font-family: 'Courier New', monospace; color: #d4d4d4; min-height: 100vh; padding: 20px; display: flex; flex-direction: column; align-items: center; justify-content: center; }
    h2 { font-size: 2em; margin-bottom: 30px; color: #8b7355; letter-spacing: 3px; text-transform: uppercase; font-weight: 300; }
    .form-container { background: #1a1a1a; border: 1px solid #333; border-radius: 4px; padding: 25px; margin-bottom: 30px; max-width: 800px; width: 100%; }
    .doc-type-selector { display: flex; gap: 15px; margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #2a2a2a; }
    .doc-type-btn { flex: 1; background: #2a2a2a; color: #d4d4d4; border: 2px solid #3a3a3a; padding: 12px 20px; border-radius: 4px; cursor: pointer; font-family: 'Courier New', monospace; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; transition: all 0.3s; }
    .doc-type-btn:hover { background: #333; border-color: #8b7355; }
    .doc-type-btn.wanted-btn:hover { border-color: #c0392b; }
    .doc-type-btn.active { background: #8b7355; border-color: #8b7355; color: #fff; }
    .doc-type-btn.active.wanted-btn { background: #c0392b; border-color: #c0392b; }
    .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 12px; margin-bottom: 15px; }
    input[type="text"], input[type="password"] { width: 100%; background: #0f0f0f; color: #d4d4d4; border: 1px solid #2a2a2a; border-radius: 2px; padding: 10px 12px; font-size: 13px; font-family: 'Courier New', monospace; transition: border-color 0.2s; }
    input[type="text"]:focus, input[type="password"]:focus { outline: none; border-color: #8b7355; background: #151515; }
    input[type="text"]::placeholder, input[type="password"]::placeholder { color: #505050; }
    .photo-upload { display: flex; align-items: center; gap: 12px; margin-top: 15px; padding-top: 15px; }
    .photo-upload label { color: #8b7355; font-size: 13px; }
    input[type="file"] { color: #d4d4d4; font-size: 12px; font-family: 'Courier New', monospace; }
    input[type="file"]::file-selector-button { background: #2a2a2a; color: #d4d4d4; border: 1px solid #3a3a3a; padding: 6px 12px; border-radius: 2px; cursor: pointer; font-family: 'Courier New', monospace; font-size: 12px; }
    input[type="file"]::file-selector-button:hover { background: #333; border-color: #8b7355; }
    .search-container { display: none; gap: 10px; margin-top: 20px; padding-top: 20px; border-top: 1px solid #2a2a2a; }
    .search-container input { flex-grow: 1; }
    .search-btn, .save-btn { background: #4a4a4a; color: #fff; border: 2px solid #5a5a5a; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-family: 'Courier New', monospace; font-size: 14px; transition: all 0.3s; }
    .search-btn:hover { background: #555; border-color: #c0392b; }
    .save-btn { width: 100%; margin-top: 15px; background-color: #3e634d; border-color: #5a7d6a; }
    .save-btn:hover { background: #4a7a5e; border-color: #83b399; }
    .card-container { position: relative; width: 800px; max-width: 100%; height: 500px; margin: 0 auto 20px; background-size: cover; background-position: center; border: 2px solid #6b5943; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.7); overflow: hidden; transition: background-color 0.3s, border-color 0.3s; }
    .card-container.visa { background-image: url('cayo.png'); }
    .card-container.fishing { background-image: url('peche.png'); }
    .card-container.wanted { background-image: linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.4)), radial-gradient(ellipse at center, #e4d5b7 0%, #c8b898 100%); border-color: #a02c2c; }
    .card-container.visa::before, .card-container.fishing::before { content: ''; position: absolute; inset: 0; background: rgba(0, 0, 0, 0.3); pointer-events: none; z-index: 1; }
    .card-container > * { position: relative; z-index: 2; }
    .card-overlay { position: absolute; inset: 0; background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 500"><defs><pattern id="texture" x="0" y="0" width="4" height="4" patternUnits="userSpaceOnUse"><rect width="4" height="4" fill="%23000" opacity="0.03"/></pattern></defs><rect width="800" height="500" fill="url(%23texture)"/></svg>'); pointer-events: none; }
    .card-header { position: absolute; top: 0; left: 0; right: 0; background: rgba(30, 25, 20, 0.85); padding: 12px 20px; text-align: center; font-size: 20px; font-weight: bold; letter-spacing: 4px; color: #d4b896; border-bottom: 2px solid #8b7355; transition: background 0.3s, color 0.3s, border-color 0.3s; }
    .card-container.wanted .card-header { background: rgba(80, 20, 20, 0.9); color: #ffdddd; border-bottom-color: #a02c2c; }
    .logo { position: absolute; top: 65px; right: 30px; width: 80px; height: 80px; border: 2px solid #6b5943; border-radius: 50%; background: #1e1914; display: flex; align-items: center; justify-content: center; font-size: 10px; color: #8b7355; text-align: center; line-height: 1.3; padding: 10px; }
    .photo { position: absolute; top: 80px; left: 40px; width: 160px; height: 200px; border: 3px solid #6b5943; background: #1a1612; overflow: hidden; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4); display: flex; align-items: center; justify-content: center; color: #505050; font-size: 13px; }
    .card-container.wanted .photo { border-color: #501010; }
    .photo img { width: 100%; height: 100%; object-fit: cover; filter: sepia(0.15) contrast(1.1); }
    .card-container.wanted .photo img { filter: grayscale(1) sepia(0.3) contrast(1.2); }
    .card-info { position: absolute; top: 170px; left: 230px; right: 140px; font-size: 14px; text-align: left; color: #ffffff; line-height: 2.1; font-family: 'Courier New', monospace; }
    .card-info div { display: flex; padding: 3px 0; border-bottom: 1px solid rgba(255, 255, 255, 0.2); }
    .card-container.wanted .card-info div { border-bottom: 1px solid rgba(0, 0, 0, 0.4); }
    .card-info strong, .card-info span { text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.8); }
    .card-container.wanted .card-info strong, .card-container.wanted .card-info span { color: #1a1a1a; text-shadow: none; }
    .card-info strong { min-width: 160px; font-weight: bold; color: #ffffff; }
    .stamp { position: absolute; bottom: 90px; right: 40px; width: 100px; height: 100px; border: 3px solid #6b4e8d; border-radius: 50%; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #6b4e8d; font-size: 16px; font-weight: bold; transform: rotate(-15deg); opacity: 0.7; }
    .stamp.fishing { border-color: #4a8db0; color: #4a8db0; }
    .stamp.wanted { border-color: #c0392b; color: #c0392b; opacity: 0.8; }
    .stamp.expired { border-color: #c0392b; color: #c0392b; opacity: 0.8; }
    .stamp-text { font-size: 10px; margin-top: 3px; }
    .card-footer { position: absolute; bottom: 15px; left: 40px; right: 40px; text-align: center; font-size: 10px; color: rgba(255, 255, 255, 0.6); border-top: 1px solid rgba(255, 255, 255, 0.2); padding-top: 8px; font-family: 'Courier New', monospace; letter-spacing: 1px; }
    .card-container.wanted .card-footer { color: #1a1a1a; border-top-color: rgba(0, 0, 0, 0.4); }
    .wanted-overlay-text { display: none; position: absolute; top: 80px; left: 230px; font-size: 70px; font-weight: 900; color: #c0392b; transform: rotate(-10deg); text-shadow: 2px 2px 4px rgba(0,0,0,0.5); letter-spacing: 5px; opacity: 0.8; border: 5px double #c0392b; padding: 5px 20px; }
    .action-buttons { display: flex; gap: 15px; width: 800px; max-width: 100%; justify-content: center; }
    .download-btn { flex: 1; background: #8b7355; color: #fff; border: none; padding: 12px 20px; border-radius: 4px; cursor: pointer; font-family: 'Courier New', monospace; font-size: 14px; font-weight: bold; margin-top: 20px; transition: all 0.3s; text-transform: uppercase; letter-spacing: 2px; }
    .download-btn:hover { background: #a08868; box-shadow: 0 5px 15px rgba(139, 115, 85, 0.4); }
    .db-controls { display: none; gap: 10px; margin-top: 10px; }
    .db-btn { flex: 1; background-color: #4a6e8a; }
    .db-btn:hover { background-color: #5a86a8; }
    .login-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 1000; display: flex; align-items: center; justify-content: center; }
    .login-box { background: #1a1a1a; border: 1px solid #333; padding: 30px; border-radius: 4px; width: 400px; max-width: 90%; text-align: center; }
    .login-box h3 { color: #8b7355; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 2px; }
    .login-box input { margin-bottom: 15px; }
    .login-box .login-btn { width: 100%; background: #8b7355; color: #fff; border: none; padding: 12px; cursor: pointer; font-family: 'Courier New', monospace; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; }
  </style>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
</head>
<body>

  <div id="admin-login-overlay" class="login-overlay">
    <div class="login-box">
      <h3>Accès Administrateur</h3>
      <input type="text" id="admin-user" placeholder="Utilisateur...">
      <input type="password" id="admin-pass" placeholder="Mot de passe...">
      <button class="login-btn" onclick="handleAdminLogin()">Connexion</button>
    </div>
  </div>

  <h2>Documents Cayo Perico</h2>
  
  <div class="form-container">
    <div class="doc-type-selector">
      <button class="doc-type-btn active" data-type="visa" onclick="switchDocType('visa', this)">🛂 Visa</button>
      <button class="doc-type-btn" data-type="fishing" onclick="switchDocType('fishing', this)">🎣 Permis de Pêche</button>
      <button class="doc-type-btn wanted-btn" data-type="wanted" onclick="promptForWanted(this)">🚨 Recherché</button>
    </div>
    <div class="form-grid">
      <input type="text" id="id" placeholder="ID" oninput="updateText('id')">
      <input type="text" id="fullname" placeholder="Nom / Prénom" oninput="updateText('fullname')">
      <input type="text" id="dob" placeholder="Date de naissance" oninput="updateText('dob')">
      <input type="text" id="exp" placeholder="Date d'expiration (clic pour auto)" oninput="updateText('exp')" onclick="setExpirationDate()">
      <input type="text" id="height" placeholder="Taille" oninput="updateText('height')">
      <input type="text" id="eyes" placeholder="Couleur des yeux" oninput="updateText('eyes')">
      <input type="text" id="sex" placeholder="Sexe" oninput="updateText('sex')">
    </div>
    <div class="photo-upload">
      <label for="photo">Photo d'identité :</label>
      <input type="file" id="photo" accept="image/*" onchange="loadPhoto(event)">
    </div>
    <button class="save-btn" onclick="saveVisa()"> Enregistrer les modifications</button>
    <div class="search-container" id="search-container">
      <input type="text" id="searchInput" placeholder="Rechercher par ID...">
      <button class="search-btn" onclick="searchVisa()">Chercher</button>
    </div>
    <div class="db-controls" id="db-controls">
        <button class="download-btn db-btn" onclick="loadDatabase()"> Charger la Base de Données (Fichier)</button>
        <input type="file" id="db-loader" style="display: none;" accept=".json" onchange="handleFileSelect(event)">
        <button class="download-btn db-btn" onclick="saveAndDownloadDatabase()"> Sauvegarder la Base de Données (Fichier)</button>
    </div>
  </div>

  <div class="card-container visa" id="card">
    <div class="card-overlay"></div>
    <div class="wanted-overlay-text" id="wanted-overlay">WANTED</div>
    <div class="card-header" id="card-header">REPÚBLICA DE CAYO PERICO - VISA</div>
    <div class="logo"> REPUBLICA<br>DE<br>CAYO PERICO </div>
    <div class="photo" id="photo-container"> PHOTO </div>
    <div class="card-info">
      <div><strong>ID :</strong> <span id="id-value">-</span></div>
      <div><strong>Nom / Prénom :</strong> <span id="fullname-value">-</span></div>
      <div><strong>Date de naissance :</strong> <span id="dob-value">-</span></div>
      <div><strong>Date d'expiration :</strong> <span id="exp-value">-</span></div>
      <div><strong>Taille :</strong> <span id="height-value">-</span></div>
      <div><strong>Couleur des yeux :</strong> <span id="eyes-value">-</span></div>
      <div><strong>Sexe :</strong> <span id="sex-value">-</span></div>
    </div>
    <div class="stamp" id="stamp">
      <span id="stamp-main-text">APPROVED</span>
      <span class="stamp-text" id="stamp-sub-text">VISA</span>
    </div>
    <div class="card-footer" id="card-footer"> DOCUMENTO OFICIAL DE VIAJE | ENTRADA ÚNICA | INTRANSFERIBLE </div>
  </div>

  <div class="action-buttons">
    <button class="download-btn" onclick="downloadCard()">Télécharger le Document</button>
  </div>

  <script>
        let isAdmin = false;

    async function handleAdminLogin() {
        const user = document.getElementById('admin-user').value;
        const pass = document.getElementById('admin-pass').value;

        try {
            const response = await fetch('index.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'login', type: 'admin', user, pass })
            });
            const result = await response.json();

            if (result.status === 'success') {
                isAdmin = true;
                document.getElementById('admin-login-overlay').style.display = 'none';
            } else {
                alert('Utilisateur ou mot de passe incorrect.');
            }
        } catch (error) {
            console.error("Erreur de connexion:", error);
            alert("Erreur de communication avec le serveur.");
        }
    }

    let currentDocType = 'visa';
    let visaDatabase = new Map();

    async function fetchDatabase() {
        try {
            const response = await fetch(`database.json?v=${new Date().getTime()}`);
            if (!response.ok) {
                if (response.status === 404) {
                    console.log("database.json n'existe pas encore.");
                    visaDatabase.clear();
                    return;
                }
                throw new Error(`Erreur HTTP: ${response.status}`);
            }
            const dataArray = await response.json();
            visaDatabase.clear();
            dataArray.forEach(person => visaDatabase.set(person.id, person));
            console.log(`Base de données synchronisée: ${visaDatabase.size} entrées.`);
        } catch (error) {
            console.error("Impossible de charger la base de données:", error);
        }
    }

    function loadDatabase() {
        if (!isAdmin) {
            alert("Opération non autorisée.");
            return;
        }
        document.getElementById('db-loader').click();
    }

    function handleFileSelect(event) {
        const reader = new FileReader();
        reader.onload = function(fileEvent) {
            try {
                const dataArray = JSON.parse(fileEvent.target.result);
                visaDatabase.clear();
                dataArray.forEach(person => visaDatabase.set(person.id, person));
                alert(`Base de données chargée depuis le fichier: ${visaDatabase.size} entrées.`);
            } catch (e) {
                alert("Erreur: Fichier JSON invalide.");
            }
        };
        reader.readAsText(event.target.files[0]);
    }

    function saveAndDownloadDatabase() {
        if (!isAdmin) {
            alert("Opération non autorisée.");
            return;
        }
        const dataArray = Array.from(visaDatabase.values());
        const dataStr = JSON.stringify(dataArray, null, 4);
        const dataBlob = new Blob([dataStr], { type: 'application/json' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(dataBlob);
        link.download = 'database.json';
        link.click();
        URL.revokeObjectURL(link.href);
    }

    async function saveVisa() {
        if (!isAdmin) {
            alert("Opération non autorisée. Connectez-vous en admin.");
            return;
        }
        const id = document.getElementById('id').value.trim();
        if (!id) {
            alert("L'ID est obligatoire !");
            return;
        }
        const personData = {
            id,
            fullname: document.getElementById('fullname').value,
            dob: document.getElementById('dob').value,
            exp: document.getElementById('exp').value,
            height: document.getElementById('height').value,
            eyes: document.getElementById('eyes').value,
            sex: document.getElementById('sex').value,
            photoUrl: document.querySelector('#photo-container img')?.src || ''
        };

        try {
            const response = await fetch('index.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ data: personData })
            });
            const result = await response.json();
            if (result.status === 'success') {
                alert(`Données pour l'ID ${id} enregistrées.`);
            } else {
                alert(`Erreur du serveur : ${result.message}`);
            }
        } catch (error) {
            console.error("Erreur d'enregistrement:", error);
            alert("Erreur de communication avec le serveur.");
        }
    }

    async function searchVisa() {
        await fetchDatabase();
        
        const searchTerm = document.getElementById('searchInput').value.trim();
        if (!searchTerm) {
            alert("Veuillez entrer un ID à rechercher.");
            return;
        }
        const person = visaDatabase.get(searchTerm);
        if (person) {
            populateCard(person);
        } else {
            alert("Aucun individu trouvé avec cet ID.");
            clearCard();
        }
    }

    function populateCard(personData) {
        document.getElementById('id').value = personData.id || '';
        document.getElementById('fullname').value = personData.fullname || '';
        document.getElementById('dob').value = personData.dob || '';
        document.getElementById('exp').value = personData.exp || '';
        document.getElementById('height').value = personData.height || '';
        document.getElementById('eyes').value = personData.eyes || '';
        document.getElementById('sex').value = personData.sex || '';
        
        Object.keys(personData).forEach(key => {
            if (key !== 'photoUrl') updateText(key);
        });

        const container = document.getElementById('photo-container');
        if (personData.photoUrl) {
            container.innerHTML = `<img src="${personData.photoUrl}" alt="Photo de ${personData.fullname}">`;
        } else {
            container.innerHTML = 'PHOTO';
        }

        const stamp = document.getElementById('stamp');
        const stampMain = document.getElementById('stamp-main-text');
        const stampSub = document.getElementById('stamp-sub-text');
        
        stamp.className = 'stamp';
        stampMain.textContent = 'APPROVED';
        stampSub.textContent = 'VISA';

        if (personData.exp) {
            const parts = personData.exp.split('/');
            const expDate = new Date(parts[2], parts[1] - 1, parts[0]);
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            if (expDate < today) {
                stamp.classList.add('expired');
                stampMain.textContent = 'EXPIRED';
                stampSub.textContent = 'VISA';
            }
        }
    }

    function clearCard() {
        const fields = ['id', 'fullname', 'dob', 'exp', 'height', 'eyes', 'sex'];
        fields.forEach(field => {
            document.getElementById(field).value = '';
            updateText(field);
        });
        document.getElementById('photo-container').innerHTML = 'PHOTO';
        
        const stamp = document.getElementById('stamp');
        const stampMain = document.getElementById('stamp-main-text');
        const stampSub = document.getElementById('stamp-sub-text');
        stamp.className = 'stamp';
        stampMain.textContent = 'APPROVED';
        stampSub.textContent = 'VISA';
    }
    
    function setExpirationDate() {
        const expInput = document.getElementById('exp');
        const today = new Date();
        today.setMonth(today.getMonth() + 1);

        const day = String(today.getDate()).padStart(2, '0');
        const month = String(today.getMonth() + 1).padStart(2, '0');
        const year = today.getFullYear();

        const formattedDate = `${day}/${month}/${year}`;
        
        expInput.value = formattedDate;
        updateText('exp');
    }

    // --- FONCTION DE PROMPT GUARDIA MODIFIÉE ---
    async function promptForWanted(btn) {
        const password = prompt("ACCÈS GUARDIA - MOT DE PASSE REQUIS :");
        if (password === null || password === "") return; 

        try {
            const response = await fetch('index.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'login', type: 'guardia', pass: password })
            });
            const result = await response.json();

            if (result.status === 'success') {
                switchDocType('wanted', btn);
            } else {
                alert("ACCÈS REFUSÉ. MOT DE PASSE INCORRECT.");
            }
        } catch (error) {
            console.error("Erreur de connexion:", error);
            alert("Erreur de communication avec le serveur.");
        }
    }
    
    function switchDocType(type, btn) {
      currentDocType = type;
      clearCard();
      document.querySelectorAll('.doc-type-btn').forEach(b => b.classList.remove('active'));
      if (btn) btn.classList.add('active');
      const card = document.getElementById('card');
      const header = document.getElementById('card-header');
      const stamp = document.getElementById('stamp');
      const stampMain = document.getElementById('stamp-main-text');
      const stampSub = document.getElementById('stamp-sub-text');
      const footer = document.getElementById('card-footer');
      const wantedOverlay = document.getElementById('wanted-overlay');
      const searchContainer = document.getElementById('search-container');
      const dbControls = document.getElementById('db-controls');
      const expInput = document.getElementById('exp');

      wantedOverlay.style.display = 'none';
      expInput.disabled = false;

      if (type === 'wanted') {
        searchContainer.style.display = 'flex';
        dbControls.style.display = 'flex';
        card.className = 'card-container wanted';
        header.textContent = 'AVIS DE RECHERCHE - GUARDIA DE CAYO';
        stamp.className = 'stamp wanted';
        stampMain.textContent = 'WANTED';
        stampSub.textContent = 'DEAD OR ALIVE';
        footer.textContent = 'CONTACTER LA GUARDIA IMMÉDIATEMENT | RÉCOMPENSE';
        wantedOverlay.style.display = 'block';
        expInput.disabled = true;
      } else {
        searchContainer.style.display = 'none';
        dbControls.style.display = 'none';
        if (type === 'visa') {
          card.className = 'card-container visa';
          header.textContent = 'REPÚBLICA DE CAYO PERICO - VISA';
          stamp.className = 'stamp';
          stampMain.textContent = 'APPROVED';
          stampSub.textContent = 'VISA';
          footer.textContent = 'DOCUMENTO OFICIAL DE VIAJE | ENTRADA ÚNICA | INTRANSFERIBLE';
        } else if (type === 'fishing') {
          card.className = 'card-container fishing';
          header.textContent = 'REPÚBLICA DE CAYO PERICO - PERMIS DE PÊCHE';
          stamp.className = 'stamp fishing';
          stampMain.textContent = 'VALID';
          stampSub.textContent = 'PÊCHE';
          footer.textContent = 'LICENCE OFFICIELLE DE PÊCHE | EAUX TERRITORIALES | VALIDE 1 AN';
        }
      }
    }
    
    function updateText(field) {
      const value = document.getElementById(field).value || '-';
      document.getElementById(field + '-value').textContent = value;
    }

    function loadPhoto(event) {
      const file = event.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const image = document.createElement('img');
            image.src = e.target.result;
            const container = document.getElementById('photo-container');
            container.innerHTML = '';
            container.appendChild(image);
        };
        reader.readAsDataURL(file);
      }
    }
    
    function downloadCard() {
      const card = document.getElementById('card');
      const fullname = document.getElementById('fullname-value').textContent;
      let docName = 'document';
      if (currentDocType === 'visa') docName = 'visa';
      if (currentDocType === 'fishing') docName = 'permis_peche';
      if (currentDocType === 'wanted') docName = 'avis_recherche';
      const filename = fullname !== '-' ? fullname.replace(/\s+/g, '_') + '_' + docName : docName + '_cayo_perico';
      html2canvas(card, { scale: 2, useCORS: true, allowTaint: true, backgroundColor: null }).then(canvas => {
        const link = document.createElement('a');
        link.download = filename + '.png';
        link.href = canvas.toDataURL('image/png');
        link.click();
      });
    }

    window.onload = async () => {
        await fetchDatabase();
        switchDocType('visa', document.querySelector('.doc-type-btn[data-type="visa"]'));
    }
  </script>
</body>
</html>