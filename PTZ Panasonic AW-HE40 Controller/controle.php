<?php
// Função para salvar lista de câmeras em arquivo local
function salvar_cameras($cameras)
{
    file_put_contents(__DIR__ . '/cameras.json', json_encode($cameras, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// Função para carregar lista
function carregar_cameras()
{
    $arquivo = __DIR__ . '/cameras.json';
    if (file_exists($arquivo)) {
        $json = file_get_contents($arquivo);
        $cameras = json_decode($json, true);
        if (is_array($cameras))
            return $cameras;
    }
    return array();
}

// AJAX para manipular registros de câmera
if (isset($_GET['ajax'])) {
    // Proxy especial para consulta dos presets PE (evita CORS)
    if ($_GET['ajax'] === 'proxy_pe') {
        $ip = $_GET['ip'] ?? '';
        $pe = $_GET['pe'] ?? '';
        if (!$ip || !preg_match('/^\d+\.\d+\.\d+\.\d+$/', $ip) || !preg_match('/^(00|01|02)$/', $pe)) {
            header("HTTP/1.1 400 Bad Request");
            exit("Parâmetro inválido");
        }
        $url = "http://$ip/cgi-bin/aw_ptz?cmd=%23PE$pe&res=1";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $resposta = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($resposta === false || $status !== 200) {
            header('HTTP/1.1 502 Bad Gateway');
            exit('Falha ao consultar a câmera.');
        }
        header('Content-Type: text/plain; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        echo $resposta;
        exit;
    }

    // BLOCO ADICIONADO PARA STATUS DA CÂMERA (AutoIris e AF)
    if ($_GET['ajax'] === 'camera_status') {
        $ip = $_GET['ip'] ?? '';
        $focusUrl = "http://$ip/cgi-bin/aw_ptz?cmd=%23D1&res=1";
        $irisUrl  = "http://$ip/cgi-bin/aw_cam?cmd=QRS&res=1";
        $focus = @file_get_contents($focusUrl);
        $iris  = @file_get_contents($irisUrl);
        header('Content-Type: application/json');
        echo json_encode([
            'focus' => trim($focus),
            'iris'  => trim($iris)
        ]);
        exit;
    }

    if ($_GET['ajax'] === 'cameras') {
        header('Content-Type: application/json; charset=utf-8');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nome = trim($_POST['nome'] ?? '');
            $ip = trim($_POST['ip'] ?? '');
            if ($nome && $ip) {
                $cameras = carregar_cameras();
                $cameras[$nome] = $ip;
                salvar_cameras($cameras);
                echo json_encode(['ok' => true, 'cameras' => $cameras]);
            } else {
                echo json_encode(['ok' => false, 'erro' => 'Nome e IP são obrigatórios']);
            }
        } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
            echo json_encode(['cameras' => carregar_cameras()]);
        } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
            parse_str(file_get_contents("php://input"), $delVars);
            $nome = trim($delVars['nome'] ?? '');
            if ($nome) {
                $cameras = carregar_cameras();
                unset($cameras[$nome]);
                salvar_cameras($cameras);
                echo json_encode(['ok' => true, 'cameras' => $cameras]);
            } else {
                echo json_encode(['ok' => false, 'erro' => 'Nome a remover não informado']);
            }
        }
        exit;
    } else {
        $ip = $_GET['ip'] ?? '';
        $cmd = $_GET['cmd'] ?? '';
        $cgi = ($_GET['ajax'] === 'cam') ? 'aw_cam' : 'aw_ptz';
        $url = "http://" . $ip . "/cgi-bin/" . $cgi . "?cmd=" . urlencode($cmd) . "&res=1";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        echo curl_exec($ch);
        curl_close($ch);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Panasonic AW-HE40 Controller</title>
    <style>
        body {
            background: #111;
            color: #fff;
            font-family: Arial, sans-serif;
            margin: 20px;
            width: max-content;
        }

        .container {
            transform: scale(var(--scale-control, 0.6));
            transform-origin: top left;
            display: flex;
            flex-direction: column;
        }

        .card-container {
            display: flex;
            flex-direction: row;
            gap: 10px;
        }

        .card {
            background: #1c1c1c;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 5px
        }

        h3 {
            margin: 0;
            font-size: 1.2em;
            font-weight: bold;
        }

        button {
            padding: 10px 15px;
            margin: 4px;
            background: #292929;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer
        }

        input {
            padding: 8px
        }

        .presets {
            display: grid;
            grid-template-columns: max-content repeat(9, 0fr);
            gap: 5px
        }

        .preset-btn-group {
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: 2px;
        }
        .preset-btn-main {
            min-width: 32px;
            font-weight: bold;
        }
        .preset-btn-main.active {
            background: #FFD600 !important;
            color: #111 !important;
        }
        .preset-action-toggle-group {
            display: inline-flex;
            gap: 4px;
            margin-left: 12px;
        }
        .preset-toggle-action-btn {
            padding: 7px 13px !important;
            margin: 0 0 0 2px;
            font-size: 0.95em;
            border-radius: 6px;
            background: #225e22;
            color: #fff;
            border: none;
            outline: none;
        }
        .preset-toggle-action-btn.del {
            background: #9d2323;
        }
        .preset-toggle-action-btn.active {
            background: #FFD600;
            color: #111;
        }
        .joystickstyle{
            width: 180px;
            height: 180px;
            position: relative;
            margin:auto;
        }

        .fader-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            height: 260px;
            width: 48px;
            justify-content: center;
            margin: auto;
            user-select: none;
            touch-action: none;
        }

        .fader-label {
            font-size: 1.1em;
            margin: 2px 0 2px 0;
            font-family: Arial, sans-serif;
        }

        .fader-track {
            background: repeating-linear-gradient(to bottom,
                    #494949 0px,
                    #222 12px,
                    #494949 24px);
            border-radius: 6px;
            width: 26px;
            height: 180px;
            position: relative;
            margin: 2px 0 2px 0;
            border: 2px solid #555;
            box-shadow: 0 0 6px #0009 inset;
        }

        .fader-thumb {
            width: 38px;
            height: 40px;
            border-radius: 8px;
            background: linear-gradient(90deg, #111 0%, #333 80%);
            border: 2px solid #444;
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            cursor: pointer;
            box-shadow: 0 0 8px #0009, 0 2px 8px #2224 inset;
            transition: background 0.2s, top 0.2s;
        }

        .fader-thumb:active {
            background: #444;
        }

        .camera-list-row {
            align-items: center;
            gap: 0.5em;
            margin-bottom: 5px;
            display: grid;
            grid-auto-flow: column dense;
            align-items: stretch;
            justify-content: stretch;
            align-content: stretch;
        }

        .camera-list-select {
            flex: 1;
        }

        .scale-controls {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 15px;
            position: absolute;
            z-index: 1;
            right: 30px;
            top: 10px;
        }

        .scale-controls label {
            font-size: 1em;
        }

        .scale-range {
            width: 120px;
        }

        /* CSS para identificar presets salvos automaticamente */
        .preset-salvo{
            background:#838383 !important;
        }
    </style>
</head>

<body>
    <div class="scale-controls">
        <label for="scale-slider">Zoom da interface:</label>
        <input type="range" id="scale-slider" class="scale-range" min="0.3" max="1.3" step="0.01" value="0.6" />
        <span id="scale-value">0.60x</span>
    </div>

    <div class="container">
    <div class="card">
            <form id="camera-save-form" style="display:flex;align-items:center;gap:10px;">
                <label for="cameraName" style="margin:0;">Nome:</label>
                <input id="cameraName" placeholder="Sala 1" style="width:110px;" maxlength="36">
                <label for="cameraIP" style="margin:0;">IP:</label>
                <input id="cameraIP" value="10.2.1.12" style="width:110px;">
                <button type="submit">Salvar</button>
            </form>
        </div>
        <div class="card-container">

        <div class="card">
            <h3>PTZ</h3>
            <div class="joystickstyle">
                <img src="images/joystick-base.png" style="width:100%;height:100%;" />
                <div id="stick1" style="position: absolute; left:57px; top:57px; width:65px; height:65px;">
                    <img src="images/joystick-red.png" style="width:65px;height:65px;" />
                </div>
            </div>
        </div>

        <script>
            // Joystick base code adapted from HTML-Joysticks-master

            class JoystickController {
                constructor(stickID, maxDistance, deadzone) {
                    this.id = stickID;
                    let stick = document.getElementById(stickID);

                    this.dragStart = null;
                    this.touchId = null;
                    this.active = false;
                    this.value = { x: 0, y: 0 };
                    let self = this;
                    function handleDown(event) {
                        self.active = true;
                        stick.style.transition = '0s';
                        event.preventDefault();
                        if (event.changedTouches)
                            self.dragStart = { x: event.changedTouches[0].clientX, y: event.changedTouches[0].clientY };
                        else
                            self.dragStart = { x: event.clientX, y: event.clientY };
                        if (event.changedTouches)
                            self.touchId = event.changedTouches[0].identifier;
                    }
                    function handleMove(event) {
                        if (!self.active) return;
                        let touchmoveId = null;
                        if (event.changedTouches) {
                            for (let i = 0; i < event.changedTouches.length; i++) {
                                if (self.touchId == event.changedTouches[i].identifier) {
                                    touchmoveId = i;
                                    event.clientX = event.changedTouches[i].clientX;
                                    event.clientY = event.changedTouches[i].clientY;
                                }
                            }
                            if (touchmoveId == null) return;
                        }
                        const xDiff = event.clientX - self.dragStart.x;
                        const yDiff = event.clientY - self.dragStart.y;
                        const angle = Math.atan2(yDiff, xDiff);
                        const distance = Math.min(maxDistance, Math.hypot(xDiff, yDiff));
                        const xPosition = distance * Math.cos(angle);
                        const yPosition = distance * Math.sin(angle);
                        stick.style.transform = `translate3d(${xPosition}px, ${yPosition}px, 0px)`;
                        // deadzone
                        const distance2 = (distance < deadzone) ? 0 : maxDistance / (maxDistance - deadzone) * (distance - deadzone);
                        const xPosition2 = distance2 * Math.cos(angle);
                        const yPosition2 = distance2 * Math.sin(angle);
                        const xPercent = parseFloat((xPosition2 / maxDistance).toFixed(4));
                        const yPercent = parseFloat((yPosition2 / maxDistance).toFixed(4));
                        self.value = { x: xPercent, y: yPercent };
                        // Send PTZ commands based on joystick position:
                        ptzControlByJoystick(xPercent, yPercent);
                    }
                    function handleUp(event) {
                        if (!self.active) return;
                        if (event.changedTouches && self.touchId != event.changedTouches[0].identifier) return;
                        stick.style.transition = '.2s';
                        stick.style.transform = `translate3d(0px, 0px, 0px)`;
                        self.value = { x: 0, y: 0 };
                        self.touchId = null;
                        self.active = false;
                        // Stop PTZ movement
                        stopPTZ();
                    }
                    stick.addEventListener('mousedown', handleDown);
                    stick.addEventListener('touchstart', handleDown);
                    document.addEventListener('mousemove', handleMove, { passive: false });
                    document.addEventListener('touchmove', handleMove, { passive: false });
                    document.addEventListener('mouseup', handleUp);
                    document.addEventListener('touchend', handleUp);
                }
            }
            function stopPTZ() { send('#PTS5050'); }
            let joystick1 = new JoystickController("stick1", 64, 8);
            function ptzControlByJoystick(x, y) {
                let threshold = 0.3;
                if (Math.abs(x) < threshold && Math.abs(y) < threshold) return;
                if (!ptzControlByJoystick.lastCmd) ptzControlByJoystick.lastCmd = "";
                x = Math.max(-1, Math.min(1, x));
                y = -Math.max(-1, Math.min(1, y));
                if (Math.abs(x) >= threshold || Math.abs(y) >= threshold) {
                    let cmd = getPTZCommandXY(x, y, threshold);
                    if (cmd !== ptzControlByJoystick.lastCmd) {
                        send(cmd);
                        ptzControlByJoystick.lastCmd = cmd;
                    }
                }
            }
            joystick1.onStop = stopPTZ;
            function getPTZCommandXY(x, y, threshold) {
                let min = 30, max = 70, mid = 50;
                function mapAxis(val) {
                    let absval = Math.abs(val);
                    if (absval < threshold) return mid;
                    let pct = (absval - threshold) / (1 - threshold);
                    if (pct < 0) pct = 0;
                    if (pct > 1) pct = 1;
                    if (val < -threshold) {
                        return Math.round(mid - pct * (mid - min));
                    } else if (val > threshold) {
                        return Math.round(mid + pct * (max - mid));
                    } else {
                        return mid;
                    }
                }
                let pan = mapAxis(x);
                let tilt = mapAxis(y);
                pan = String(pan).padStart(2, "0");
                tilt = String(tilt).padStart(2, "0");
                return `#PTS${pan}${tilt}`;
            }
        </script>
        <div class="card">
            <h3>Brilho</h3>
            <div class="fader-container" id="bright-fader-wrap">
                <span class="fader-label">+</span>
                <div class="fader-track" id="bright-fader-track">
                    <div class="fader-thumb" id="bright-fader-thumb" style="top: 65px;"></div>
                </div>
                <span class="fader-label">-</span>
            </div>
        </div>

        <div class="card">
            <h3>Zoom</h3>
            <div class="fader-container" id="zoom-fader-wrap">
                <span class="fader-label">+</span>
                <div class="fader-track" id="zoom-fader-track">
                    <div class="fader-thumb" id="zoom-fader-thumb" style="top: 65px;"></div>
                </div>
                <span class="fader-label">-</span>
            </div>
        </div>

        <div class="card">
            <h3>Focus</h3>
            <div class="fader-container" id="focus-fader-wrap">
                <span class="fader-label">+</span>
                <div class="fader-track" id="focus-fader-track">
                    <div class="fader-thumb" id="focus-fader-thumb" style="top: 65px;"></div>
                </div>
                <span class="fader-label">-</span>
            </div>
        </div>

        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />

        <div class="card">
            <h3>Auto Iris</h3>
            <div style="display: flex; flex-direction: column; gap: 10px;">
                <button id="auto-iris-toggle"
                    style="background: #838383; color: #fff; font-size: 1.3em; display: flex; align-items: center; gap: 0.5em; padding: 8px 18px;justify-content: center;"
                    onclick="toggleAutoIris()">
                    <i id="auto-iris-icon" class="fa-solid fa-lightbulb" style="color: #FFD600;"></i>

                </button>
            </div>
            <h3>Auto foco</h3>
            <div style="display: flex; flex-direction: column; gap: 10px;">
                <button id="af-toggle-btn" onclick="toggleAfMode()">AF</button>
                <button onclick="sendCam('OSE:69:1')">AF AUTO</button>
            </div>
            <script>
                // Estado atual do AF: true = ON, false = OFF
                let afOn = false;
                const afBtn = document.getElementById("af-toggle-btn");
                function setAfBtnAppearance() {
                    if (afOn) {
                        afBtn.textContent = 'AF';
                        afBtn.style.background = '#838383'; // cinza escuro
                        afBtn.style.color = '#fff';
                    } else {
                        afBtn.textContent = 'AF';
                        afBtn.style.background = '#333333'; // preto claro
                        afBtn.style.color = '#fff';
                    }
                }
                async function atualizarStatusCamera() {
                    const resposta = await fetch(
                        '?ajax=camera_status&ip=' + encodeURIComponent(ip())
                    );
                    const dados = await resposta.json();
                    afOn = dados.focus === 'd11';
                    autoIrisOn = dados.iris === 'ORS:1';
                    setAfBtnAppearance();
                    setAutoIrisAppearance();
                }
                function toggleAfMode() {
                    afOn = !afOn;
                    if (afOn) {
                        sendCam('OAF:1');
                    } else {
                        sendCam('OAF:0');
                    }
                    setTimeout(atualizarStatusCamera, 500);
                }
                setAfBtnAppearance();
            </script>
        </div>
        <script>
            // Auto Iris state: true = ON, false = OFF
            let autoIrisOn = false;
            const autoIrisBtn = document.getElementById("auto-iris-toggle");
            const autoIrisIcon = document.getElementById("auto-iris-icon");
            const autoIrisLabel = document.getElementById("auto-iris-label");

            function setAutoIrisAppearance() {
                if (autoIrisOn) {
                    autoIrisBtn.style.background = "#838383"; // ON background
                    autoIrisIcon.style.color = "#FFD600";     // ON = yellow

                } else {
                    autoIrisBtn.style.background = "#333333"; // OFF background
                    autoIrisIcon.style.color = "#fff";         // OFF = white

                }
            }

            function toggleAutoIris() {
                autoIrisOn = !autoIrisOn;
                if (autoIrisOn) {
                    sendCam('ORS:1');
                } else {
                    sendCam('ORS:0');
                }
                setTimeout(atualizarStatusCamera, 500);
            }
            setAutoIrisAppearance();
        </script>


        <div class="card">
            <h3 style="display: flex; align-items: center;">
                Presets
                <span class="preset-action-toggle-group">
                    <button id="presetSaveModeBtn" class="preset-toggle-action-btn" title="Modo Salvar">Salvar</button>
                    <button id="presetDelModeBtn" class="preset-toggle-action-btn del" title="Modo Deletar">Del</button>
                </span>
            </h3>
            <div class="presets" id="presets"></div>
        </div>
        <div class="card">
            <div id="camera-list">
                <strong>Câmeras salvas:</strong>
                <div id="camera-list-content"></div>
            </div>
        </div>
    </div></div>

    <script>
        // --------- Detecção automática dos presets salvos da câmera --------
        /**
         * Consulta simultânea dos comandos PE e atualiza o status dos botões dos presets
         * Sempre utiliza o IP atual selecionado/interface
         * Remove os estilos da câmera anterior ao mudar de câmera
         *
         * A conversão do bitmap segue: o LSB (bit 0) é o Preset 1, o bit 1 é o Preset 2, etc.
         * O bitmap deve ser interpretado usando BigInt/bitwise para funcionar com todos os retornos reais das câmeras.
         */
        function parsePresetBitmap(hexStr, nbits) {
            // Use BigInt para garantir que grandes hexadecimais sejam lidos corretamente
            let val = BigInt('0x' + hexStr);
            let exists = Array(nbits).fill(false);
            for (let i = 0; i < nbits; i++) {
                // Bit i: preset base + i (preset 1 = bit 0)
                exists[i] = (val & (1n << BigInt(i))) !== 0n;
            }
            return exists;
        }

        function validarPanasonicPE00() {
            // Teste obrigatório: PE00 = 01C0009CB3 deve detectar 1,2,5,6,8,11,12,13,16 como existentes
            let hexStr = "01C0009CB3";
            let nbits = 40;
            let bitmap = parsePresetBitmap(hexStr, nbits);

            let expected = [1,2,5,6,8,11,12,13,16];
            let found = [];
            for (let i = 0; i < 40; i++) {
                if (bitmap[i]) found.push(i+1);
            }
            let missing = expected.filter(n => !found.includes(n));
            if (missing.length > 0) {
                // Falhou. Bloqueia atualização
                alert("Erro crítico na conversão dos presets Panasonic!\n\nFaltando: "+missing.join(", ")+"\nConsulte o desenvolvedor.\n\nHex PE00 usado para o teste: " + hexStr + "\nDetectado: " + JSON.stringify(found));
                throw new Error("Falha na conversão dos bits do preset Panasonic.");
            }
        }

        function atualizarStatusPresets(ipAtual) {
            // Limpa todos os status de presets marcados (de qualquer câmera anterior)
            for (let n = 1; n <= 100; n++) {
                let btns = document.querySelectorAll('.preset-btn-main[data-preset="'+n+'"]');
                btns.forEach(btn => {
                    btn.classList.remove('preset-salvo');
                    btn.style.background = ""; // restaura fundo original
                });
            }
            if (!ipAtual || !ipAtual.trim()) return;

            // Validação obrigatória: só prosseguir se implementação correta
            try { validarPanasonicPE00(); } catch (e) { return; }

            const urls = [
                `?ajax=proxy_pe&ip=${encodeURIComponent(ipAtual)}&pe=00`,
                `?ajax=proxy_pe&ip=${encodeURIComponent(ipAtual)}&pe=01`,
                `?ajax=proxy_pe&ip=${encodeURIComponent(ipAtual)}&pe=02`,
            ];
            Promise.all(urls.map(
                url => fetch(url, { method: "GET", cache: "no-store" })
                        .then(resp => resp.ok ? resp.text() : Promise.reject(new Error("HTTP " + resp.status)))
            )).then(respostas => {
                // Array que representa se um preset existe (base=1)
                const presetsSalvos = new Array(101).fill(false);
                for (let k = 0; k < 3; k++) {
                    const resposta = respostas[k].replace(/[\r\n]+/g,"").trim();

                    // --- NOVO BLOCO PARA EXTRAÇÃO DO HEX E DEBUG ---
                    let hexStr = '';

                    if (resposta.startsWith('pE00')) {
                        hexStr = resposta.substring(4);
                    } else if (resposta.startsWith('pE01')) {
                        hexStr = resposta.substring(4);
                    } else if (resposta.startsWith('pE02')) {
                        hexStr = resposta.substring(4);
                    }

                    if (!hexStr) continue;

                    console.log('Resposta Panasonic:', resposta);
                    console.log('Hex extraído:', hexStr);

                    // PE00 tem 40 bits, PE01 40, PE02 20
                    let base = (k === 0 ? 1 : (k === 1 ? 41 : 81));
                    let nbits = (k === 2 ? 20 : 40);

                    // Interpretação correta: LSB é preset base (preset 1, 41 ou 81)
                    const existsArr = parsePresetBitmap(hexStr, nbits);
                    for (let i = 0; i < nbits; i++) {
                        let presetNumber = base + i;
                        presetsSalvos[presetNumber] = existsArr[i];
                        if (existsArr[i]) {
                            console.log('Preset encontrado:', presetNumber);
                        }
                    }
                }
                // Atualiza todos os botões presentes
                for (let n = 1; n <= 100; n++) {
                    let btns = document.querySelectorAll('.preset-btn-main[data-preset="'+n+'"]');
                    btns.forEach(btn => {
                        btn.classList.remove('preset-salvo');
                        btn.style.background = "";
                        if (presetsSalvos[n]) {
                            btn.classList.add('preset-salvo');
                        }
                    });
                }
            }).catch(function() {
                // Erro: Limpa todas as marcações visuais
                for (let n = 1; n <= 100; n++) {
                    let btns = document.querySelectorAll('.preset-btn-main[data-preset="'+n+'"]');
                    btns.forEach(btn => {
                        btn.classList.remove('preset-salvo');
                        btn.style.background = "";
                    });
                }
            });
        }
        // --------------------------------------------------------------

        let presetActionMode = 0;
        const presetSaveModeBtn = document.getElementById('presetSaveModeBtn');
        const presetDelModeBtn = document.getElementById('presetDelModeBtn');

        function updatePresetActionButtons() {
            presetSaveModeBtn.classList.toggle('active', presetActionMode === 1);
            presetDelModeBtn.classList.toggle('active', presetActionMode === 2);
        }

        presetSaveModeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            presetActionMode = (presetActionMode === 1) ? 0 : 1;
            if (presetActionMode === 1) presetDelModeBtn.classList.remove('active');
            updatePresetActionButtons();
            renderPresets();
        });
        presetDelModeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            presetActionMode = (presetActionMode === 2) ? 0 : 2;
            if (presetActionMode === 2) presetSaveModeBtn.classList.remove('active');
            updatePresetActionButtons();
            renderPresets();
        });
        updatePresetActionButtons();

        (function () {
            const scaleSlider = document.getElementById('scale-slider');
            const scaleValLabel = document.getElementById('scale-value');
            function updateScale(val) {
                const v = parseFloat(val);
                document.documentElement.style.setProperty('--scale-control', v);
                scaleValLabel.textContent = v.toFixed(2) + "x";
            }
            if (window.localStorage) {
                let saved = localStorage.getItem('ava-scale-control');
                if (saved !== null) {
                    scaleSlider.value = saved;
                    updateScale(saved);
                } else {
                    updateScale(scaleSlider.value);
                }
            } else {
                updateScale(scaleSlider.value);
            }
            scaleSlider.addEventListener('input', e => {
                let val = e.target.value;
                updateScale(val);
                if (window.localStorage) localStorage.setItem('ava-scale-control', val);
            });
        })();

        // Função para atualizar lista e setar evento de clique nas câmeras salvas
        function atualizarListaCameras() {
            fetch('?ajax=cameras')
                .then(r => r.json())
                .then(data => {
                    let div = document.getElementById('camera-list-content');
                    div.innerHTML = '';
                    if (data.cameras && Object.keys(data.cameras).length) {
                        for (let nome in data.cameras) {
                            let ip = data.cameras[nome];
                            let row = document.createElement('div');
                            row.className = "camera-list-row";

                            let selBtn = document.createElement('button');
                            selBtn.innerText = nome;
                            // Ao clicar em uma câmera, carrega o IP e ATUALIZA os presets deste IP
                            selBtn.onclick = function () {
                                document.getElementById('cameraIP').value = ip;
                                document.getElementById('cameraName').value = nome;
                                atualizarStatusPresets(ip);
                                atualizarStatusCamera();
                            };
                            selBtn.title = "Carregar este IP";

                            let delBtn = document.createElement('button');
                            delBtn.innerHTML = '🗑️';
                            delBtn.title = 'Remover esta câmera';
                            delBtn.onclick = function () {
                                if (confirm('Excluir câmera "' + nome + '"?')) {
                                    fetch('?ajax=cameras', { method: 'DELETE', body: 'nome=' + encodeURIComponent(nome) })
                                        .then(atualizarListaCameras)
                                }
                            };
                            row.appendChild(delBtn);
                            row.appendChild(selBtn);
                            div.appendChild(row);
                        }
                    } else {
                        div.innerHTML = "<em>Nenhuma câmera salva</em>";
                    }
                });
        }

        document.getElementById('camera-save-form').addEventListener('submit', function (ev) {
            ev.preventDefault();
            let nome = document.getElementById('cameraName').value.trim();
            let ip = document.getElementById('cameraIP').value.trim();
            if (!nome) { alert('Informe um nome para essa câmera'); return; }
            if (!ip) { alert('Informe o IP da câmera'); return; }
            fetch('?ajax=cameras', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'nome=' + encodeURIComponent(nome) + '&ip=' + encodeURIComponent(ip)
            }).then(() => {
                atualizarListaCameras();
                atualizarStatusPresets(ip);
            })
        });

        atualizarListaCameras();

        function ip() { return document.getElementById('cameraIP').value; }

        function send(cmd) {
            fetch('?ajax=1&ip=' + encodeURIComponent(ip()) + '&cmd=' + encodeURIComponent(cmd));
        }

        function sendCam(cmd) {
            fetch('?ajax=cam&ip=' + encodeURIComponent(ip()) + '&cmd=' + encodeURIComponent(cmd));
        }

        function brightUp() { sendCam('LIO'); }
        function brightDown() { sendCam('LIC'); }
        function brightStop() { sendCam('LIT'); }

        function callPreset(n) {
            let p = String(n - 1).padStart(2, '0');
            send('#R' + p);
        }
        function savePreset(n) {
            let p = String(n - 1).padStart(2, '0');
            send('#M' + p);
            setTimeout(() => atualizarStatusPresets(ip()), 1200); // Atualiza após tempo suficiente para gravação
        }
        function delPreset(n) {
            let p = String(n - 1).padStart(2, '0');
            send('#C' + p);
            setTimeout(() => atualizarStatusPresets(ip()), 1200); // Atualiza após tempo suficiente para delete
        }

        function renderPresets() {
            const presetsDiv = document.getElementById('presets');
            presetsDiv.innerHTML = '';
            let total = 100;
            for (let i = 1; i <= total; i++) {
                const group = document.createElement('div');
                group.className = 'preset-btn-group';

                let mainBtn = document.createElement('button');
                mainBtn.textContent = i;
                mainBtn.className = 'preset-btn-main';
                mainBtn.setAttribute('data-preset', i);

                if (presetActionMode === 1) {
                    mainBtn.title = 'Salvar este preset (posição atual)';
                    mainBtn.style.background = "#225e22";
                    mainBtn.onclick = function(e) {
                        e.preventDefault();
                        if (confirm('Salvar posição atual no Preset ' + i + '?')) {
                            savePreset(i);
                        }
                    };
                } else if (presetActionMode === 2) {
                    mainBtn.title = 'Deletar (limpar) este preset';
                    mainBtn.style.background = "#9d2323";
                    mainBtn.onclick = function(e) {
                        e.preventDefault();
                        if (confirm('Apagar este Preset ' + i + '?')) {
                            delPreset(i);
                        }
                    };
                } else {
                    mainBtn.title = 'Chamar Preset';
                    mainBtn.style.background = "";
                    mainBtn.onclick = function(e) {
                        e.preventDefault();
                        callPreset(i);
                    };
                }

                group.appendChild(mainBtn);
                presetsDiv.appendChild(group);
            }
            // Apenas atualize os destaques dos presets da câmera SELECIONADA (campo IP)
            // Ou seja: só rodar a função para a câmera "ativa", não para todas nem para IP fixo
            const ipAtual = document.getElementById('cameraIP').value.trim();
            atualizarStatusPresets(ipAtual);
        }

        renderPresets();

        const zoomFader = {
            track: document.getElementById('zoom-fader-track'),
            thumb: document.getElementById('zoom-fader-thumb'),
            minY: 0,
            maxY: 140,
            midY: 70,
            threshold: 10
        };
        const focusFader = {
            track: document.getElementById('focus-fader-track'),
            thumb: document.getElementById('focus-fader-thumb'),
            minY: 0,
            maxY: 140,
            midY: 70,
            threshold: 10
        };
        const brightFader = {
            track: document.getElementById('bright-fader-track'),
            thumb: document.getElementById('bright-fader-thumb'),
            minY: 0,
            maxY: 140,
            midY: 70,
            threshold: 10
        };

        function faderPercent(y, minY, maxY, midY) {
            if (y < midY) return -(1 - (y - minY) / (midY - minY));
            else if (y > midY) return 1 - (maxY - y) / (maxY - midY);
            else return 0;
        }
        function clamp(v, min, max) { return Math.max(min, Math.min(v, max)); }

        function makeFaderHandlers(faderObj, onMoveCommandFn, onStopFn) {
            let isDragging = false, startY = 0, startTop = 0, lastCmd = null;
            let pointerId = null;

            function setThumbPos(px) {
                px = clamp(px, faderObj.minY, faderObj.maxY);
                faderObj.thumb.style.top = px + "px";
            }

            function handleDown(e) {
                isDragging = true;

                if (e.type.startsWith('touch')) {
                    pointerId = e.touches[0].identifier;
                    startY = e.touches[0].clientY;
                } else {
                    pointerId = null;
                    startY = e.clientY;
                }

                const style = window.getComputedStyle(faderObj.thumb);
                startTop = parseInt(style.top);
                faderObj.thumb.style.transition = '0s';
                document.body.style.userSelect = 'none';
                e.preventDefault();
            }

            function handleMove(e) {
                if (!isDragging) return;

                let clientY;
                if (e.type.startsWith('touch')) {
                    if (!e.touches.length) return;
                    let found = false;
                    for (let t of e.touches) {
                        if (typeof pointerId === "number" && t.identifier === pointerId) {
                            clientY = t.clientY;
                            found = true;
                            break;
                        }
                    }
                    if (!found) return;
                } else {
                    clientY = e.clientY;
                }

                let dy = clientY - startY;
                let newTop = clamp(startTop + dy, faderObj.minY, faderObj.maxY);
                setThumbPos(newTop);

                let v = faderPercent(newTop, faderObj.minY, faderObj.maxY, faderObj.midY);

                if (Math.abs(v) < (faderObj.threshold / (faderObj.maxY - faderObj.minY) / 2)) v = 0;
                v = clamp(v, -1, 1);

                let cmd = onMoveCommandFn(v);
                if (cmd !== lastCmd) {
                    if (cmd) cmd();
                    lastCmd = cmd;
                }
            }

            function handleUp(e) {
                if (!isDragging) return;
                isDragging = false;
                faderObj.thumb.style.transition = '.2s';
                setThumbPos(faderObj.midY);
                document.body.style.userSelect = '';
                lastCmd = null;
                pointerId = null;
                if (onStopFn) onStopFn();
            }

            faderObj.thumb.addEventListener('mousedown', handleDown);
            document.addEventListener('mousemove', handleMove, { passive: false });
            document.addEventListener('mouseup', handleUp);
            faderObj.thumb.addEventListener('touchstart', handleDown);
            document.addEventListener('touchmove', handleMove, { passive: false });
            document.addEventListener('touchend', handleUp);
            faderObj.thumb.addEventListener('mouseleave', function (ev) { if (isDragging) handleUp(ev); });
            window.addEventListener('blur', function (ev) { if (isDragging) handleUp(ev); });
            setThumbPos(faderObj.midY);
        }

        function buildZoomCommand(v) {
            let threshold = 0.2;
            if (v < -threshold) return () => send('#Z70');
            if (v > threshold) return () => send('#Z30');
            return () => send('#Z50');
        }
        function buildFocusCommand(v) {
            let threshold = 0.2;
            if (v < -threshold) return () => send('#F70');
            if (v > threshold) return () => send('#F30');
            return () => send('#F50');
        }
        function buildBrightCommand(v) {
            let threshold = 0.2;
            if (v < -threshold) return () => sendCam('LIO');
            if (v > threshold) return () => sendCam('LIC');
            return () => sendCam('LIT');
        }

        function stopZoom() { send('#Z50'); }
        function stopFocus() { send('#F50'); }
        function stopBright() { sendCam('LIT'); }

        makeFaderHandlers(zoomFader, buildZoomCommand, stopZoom);
        makeFaderHandlers(focusFader, buildFocusCommand, stopFocus);
        makeFaderHandlers(brightFader, buildBrightCommand, stopBright);

        window.renderPresets = renderPresets;
        window.atualizarStatusPresets = atualizarStatusPresets;

        // Não roda atualizarStatusPresets automaticamente ao carregar página para não consultar câmera errada
        // Roda apenas ao selecionar/trocar câmera, ao salvar ou apagar presets

        // Como fallback, ao recarregar a tela pela primeira vez, aplica para o IP do campo atual, se houver
        document.addEventListener("DOMContentLoaded", function() {
            let ipPadrao = document.getElementById('cameraIP').value.trim();
            if (ipPadrao) atualizarStatusPresets(ipPadrao);
        });
    </script>
</body>
</html>