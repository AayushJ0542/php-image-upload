<?php
session_start();

if (empty($_SESSION['scan_session'])) {
    $_SESSION['scan_session'] = uniqid('jw_', true);
}
$sessionId = $_SESSION['scan_session'];

$protocol  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host      = $_SERVER['HTTP_HOST'];
$basePath  = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$mobileUrl = $protocol . '://' . $host . $basePath . '/mobile.php?session=' . urlencode($sessionId);
$qrApiUrl  = 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' . urlencode($mobileUrl);

// Initialise session JSON file early
require_once __DIR__ . '/session-helper.php';
initSession($sessionId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Jewelry Photo Sync</title>
  <link rel="stylesheet" href="css/index.css">
</head>
<body>

<div class="page-wrap">

  <!-- Form card -->
  <div class="form-card">
    <div class="form-header">
      <span class="form-icon">💎</span>
      <div>
        <h1>Jewelry Entry Form</h1>
        <p>Fill in the details below, then upload a photo via your phone.</p>
      </div>
    </div>

    <form id="jewelry-form" novalidate>

      <div class="form-row two-col">
        <div class="field-group">
          <label for="customer-name">Customer Name <span class="req">*</span></label>
          <input type="text" id="customer-name" name="customer_name" placeholder="e.g. Priya Sharma" required>
        </div>
        <div class="field-group">
          <label for="product-id">Product ID <span class="req">*</span></label>
          <input type="text" id="product-id" name="product_id" placeholder="e.g. JW-00123" required>
        </div>
      </div>

      <div class="form-row two-col">
        <div class="field-group">
          <label for="category">Category <span class="req">*</span></label>
          <select id="category" name="category" required>
            <option value="" disabled selected>Select category</option>
            <option value="ring">Ring</option>
            <option value="bracelet">Bracelet</option>
            <option value="necklace">Necklace</option>
            <option value="earring">Earring</option>
            <option value="pendant">Pendant</option>
            <option value="bangle">Bangle</option>
            <option value="anklet">Anklet</option>
            <option value="brooch">Brooch</option>
            <option value="other">Other</option>
          </select>
        </div>
        <div class="field-group">
          <label for="metal">Metal Type</label>
          <select id="metal" name="metal">
            <option value="" disabled selected>Select metal</option>
            <option value="gold-22k">Gold 22K</option>
            <option value="gold-18k">Gold 18K</option>
            <option value="gold-14k">Gold 14K</option>
            <option value="silver">Silver</option>
            <option value="platinum">Platinum</option>
            <option value="rose-gold">Rose Gold</option>
            <option value="white-gold">White Gold</option>
          </select>
        </div>
      </div>

      <div class="form-row two-col">
        <div class="field-group">
          <label for="weight">Weight (grams)</label>
          <input type="number" id="weight" name="weight" placeholder="e.g. 5.4" min="0" step="0.01">
        </div>
        <div class="field-group">
          <label for="price">Price (&#8377;)</label>
          <input type="number" id="price" name="price" placeholder="e.g. 24500" min="0" step="1">
        </div>
      </div>

      <div class="form-row">
        <div class="field-group">
          <label for="description">Description / Notes</label>
          <textarea id="description" name="description" rows="3" placeholder="Gemstone details, design notes, hallmark info…"></textarea>
        </div>
      </div>

      <!-- Phone connection strip -->
      <div class="connect-strip" id="connect-strip">

        <!-- Step 1: not yet generated -->
        <div class="connect-generate" id="qr-generate">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none"
               viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
            <rect x="3" y="14" width="7" height="7" rx="1"/>
            <path stroke-linecap="round" d="M14 14h2m3 0h2M14 17v2m0 3v-1M17 14v3h3"/>
          </svg>
          <div class="connect-qr-text">
            <strong>Connect your phone</strong>
            <span>Generate a QR code to link your phone camera</span>
          </div>
          <button type="button" class="generate-qr-btn" id="generate-qr-btn">
            Generate QR Code
          </button>
        </div>

        <!-- Step 2: QR visible, waiting for scan -->
        <div class="connect-qr-side" id="qr-side" hidden>
          <img id="qr-img" src="<?= htmlspecialchars($qrApiUrl) ?>" alt="QR Code" width="100" height="100">
          <div class="connect-qr-text">
            <strong>Scan once to connect your phone</strong>
            <span>No re-scanning needed for future uploads</span>
          </div>
          <button type="button" class="end-session-btn" id="end-session-btn-qr" title="End session and regenerate QR">
            End Session
          </button>
        </div>

        <!-- Step 3: phone connected -->
        <div class="connect-badge-row" id="connect-badge-row" hidden>
          <div class="connect-badge">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none"
                 viewBox="0 0 24 24" stroke="#10b981" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
            Phone connected
          </div>
          <button type="button" class="end-session-btn" id="end-session-btn-connected">
            End Session
          </button>
        </div>

      </div>

      <!-- Upload button row -->
      <div class="form-row upload-row">
        <button type="button" class="upload-image-btn" id="open-modal" disabled>
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none"
               viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M3 7h2l2-3h10l2 3h2a1 1 0 011 1v11a1 1 0 01-1 1H3a1 1 0 01-1-1V8a1 1 0 011-1z"/>
            <circle cx="12" cy="13" r="3.5"/>
          </svg>
          Upload Image via Phone
        </button>
        <span class="upload-hint" id="upload-hint">Connect your phone first</span>

        <!-- Thumbnail after photo attached -->
        <div class="thumb-preview" id="thumb-preview" hidden>
          <img id="thumb-img" src="" alt="Jewelry photo">
          <div class="thumb-info">
            <span class="thumb-ok">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none"
                   viewBox="0 0 24 24" stroke="#10b981" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
              </svg>
              Photo attached
            </span>
            <button type="button" class="retake-link" id="retake-btn">Retake</button>
          </div>
        </div>
      </div>

      <div class="form-actions">
        <button type="submit" class="submit-btn">Submit Entry</button>
      </div>

    </form>
  </div>

</div>

<!-- QR / Photo popup modal -->
<div class="modal-overlay" id="modal-overlay" hidden>
  <div class="modal">
    <button class="modal-close" id="modal-close" title="Close">&times;</button>

    <!-- Left: status / instruction -->
    <div class="modal-qr">
      <h2>Photo request sent</h2>
      <div class="request-anim" id="request-anim">
        <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" fill="none"
             viewBox="0 0 24 24" stroke="#6366f1" stroke-width="1.4">
          <path stroke-linecap="round" stroke-linejoin="round"
            d="M3 7h2l2-3h10l2 3h2a1 1 0 011 1v11a1 1 0 01-1 1H3a1 1 0 01-1-1V8a1 1 0 011-1z"/>
          <circle cx="12" cy="13" r="3.5" stroke-linecap="round"/>
        </svg>
      </div>
      <div class="status-row">
        <span class="dot" id="status-dot"></span>
        <span id="status-text">Waiting for phone to capture…</span>
      </div>
      <p class="modal-hint">Your phone will vibrate / show a prompt automatically.</p>
    </div>

    <!-- Right: photo preview -->
    <div class="modal-image">
      <h2>Photo appears here</h2>
      <div id="image-placeholder">
        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="none"
             viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2">
          <path stroke-linecap="round" stroke-linejoin="round"
            d="M3 7h2l2-3h10l2 3h2a1 1 0 011 1v11a1 1 0 01-1 1H3a1 1 0 01-1-1V8a1 1 0 011-1z"/>
          <circle cx="12" cy="13" r="3.5" stroke-linecap="round"/>
        </svg>
        Photo will appear automatically
      </div>
      <img id="jewelry-image" src="" alt="Captured jewelry photo">
      <div class="modal-image-actions" id="modal-image-actions" hidden>
        <a id="download-btn" class="download-btn" href="#" download>
          <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none"
               viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
          </svg>
          Download
        </a>
        <button type="button" class="use-photo-btn" id="use-photo-btn">
          <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none"
               viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
          </svg>
          Use This Photo
        </button>
      </div>
    </div>

  </div>
</div>

<script>
  let SESSION  = <?= json_encode($sessionId) ?>;
  const INTERVAL = 2000;

  // --- DOM refs ---
  const overlay            = document.getElementById('modal-overlay');
  const openBtn            = document.getElementById('open-modal');
  const closeBtn           = document.getElementById('modal-close');
  const dot                = document.getElementById('status-dot');
  const statusText         = document.getElementById('status-text');
  const placeholder        = document.getElementById('image-placeholder');
  const imgEl              = document.getElementById('jewelry-image');
  const imgActions         = document.getElementById('modal-image-actions');
  const dlBtn              = document.getElementById('download-btn');
  const usePhotoBtn        = document.getElementById('use-photo-btn');
  const thumbPreview       = document.getElementById('thumb-preview');
  const thumbImg           = document.getElementById('thumb-img');
  const retakeBtn          = document.getElementById('retake-btn');
  const qrGenerate         = document.getElementById('qr-generate');
  const qrSide             = document.getElementById('qr-side');
  const qrImg              = document.getElementById('qr-img');
  const connectBadgeRow    = document.getElementById('connect-badge-row');
  const generateQrBtn      = document.getElementById('generate-qr-btn');
  const endSessionBtnQr    = document.getElementById('end-session-btn-qr');
  const endSessionBtnConn  = document.getElementById('end-session-btn-connected');
  const uploadHint         = document.getElementById('upload-hint');

  let connectionTimer = null;
  let photoTimer      = null;
  let currentReqId    = 0;
  let receivedUrl     = null;

  // ── Generate QR on button click ─────────────────────────────────────────
  generateQrBtn.addEventListener('click', () => {
    qrGenerate.hidden = true;
    qrSide.hidden     = false;
    startConnectionPolling();
  });

  // ── End Session ──────────────────────────────────────────────────────────
  function doEndSession() {
    stopConnectionPolling();
    stopPhotoPolling();
    closeModal();

    fetch('end-session.php', { method: 'POST' })
      .then(r => r.ok ? r.json() : Promise.reject())
      .then(data => {
        SESSION = data.session_id;
        qrImg.src = data.qr_url;

        // Reset UI back to "generate QR" state
        qrGenerate.hidden      = false;
        qrSide.hidden          = true;
        connectBadgeRow.hidden = true;
        openBtn.disabled       = true;
        uploadHint.textContent = 'Connect your phone first';
        thumbPreview.hidden    = true;
        receivedUrl            = null;
        currentReqId           = 0;
      })
      .catch(() => alert('Could not end session. Please refresh the page.'));
  }

  endSessionBtnQr.addEventListener('click', doEndSession);
  endSessionBtnConn.addEventListener('click', doEndSession);

  // ── Connection polling ──────────────────────────────────────────────────
  function startConnectionPolling() {
    pollConnection();
    connectionTimer = setInterval(pollConnection, INTERVAL);
  }

  function stopConnectionPolling() {
    if (connectionTimer) { clearInterval(connectionTimer); connectionTimer = null; }
  }

  function pollConnection() {
    fetch('status.php?session=' + encodeURIComponent(SESSION))
      .then(r => r.ok ? r.json() : Promise.reject())
      .then(data => {
        if (data.mobile_connected) {
          onPhoneConnected();
        }
      })
      .catch(() => {});
  }

  function onPhoneConnected() {
    stopConnectionPolling();
    qrSide.hidden          = true;
    connectBadgeRow.hidden = false;
    openBtn.disabled       = false;
    uploadHint.textContent = 'Phone is connected — click to request a photo';
  }

  // ── Photo polling (after requesting) ───────────────────────────────────
  function startPhotoPolling(reqId) {
    pollPhoto(reqId);
    photoTimer = setInterval(() => pollPhoto(reqId), INTERVAL);
  }

  function stopPhotoPolling() {
    if (photoTimer) { clearInterval(photoTimer); photoTimer = null; }
  }

  function pollPhoto(reqId) {
    fetch('status.php?session=' + encodeURIComponent(SESSION) + '&request_id=' + reqId)
      .then(r => r.ok ? r.json() : Promise.reject())
      .then(data => {
        if (data.status === 'ready') showReady(data.url);
      })
      .catch(() => {
        dot.className          = 'dot error';
        statusText.textContent = 'Connection error — retrying…';
        setTimeout(() => {
          dot.className          = 'dot';
          statusText.textContent = 'Waiting for phone to capture…';
        }, 3000);
      });
  }

  function showReady(url) {
    receivedUrl               = url;
    dot.className             = 'dot ready';
    statusText.textContent    = 'Photo received!';
    imgEl.src                 = url;
    imgEl.style.display       = 'block';
    placeholder.style.display = 'none';
    dlBtn.href                = url;
    imgActions.hidden         = false;
    stopPhotoPolling();
  }

  // ── Open modal — request a new photo ───────────────────────────────────
  openBtn.addEventListener('click', () => {
    // Reset modal state
    receivedUrl               = null;
    imgEl.src                 = '';
    imgEl.style.display       = 'none';
    placeholder.style.display = '';
    imgActions.hidden         = true;
    dot.className             = 'dot';
    statusText.textContent    = 'Sending request to phone…';

    overlay.hidden             = false;
    document.body.style.overflow = 'hidden';

    // Ask server to create a new pending request
    fetch('request-photo.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'session=' + encodeURIComponent(SESSION),
    })
      .then(r => r.ok ? r.json() : Promise.reject())
      .then(data => {
        if (data.ok) {
          currentReqId           = data.request_id;
          statusText.textContent = 'Waiting for phone to capture…';
          startPhotoPolling(currentReqId);
        }
      })
      .catch(() => {
        statusText.textContent = 'Could not reach server. Please retry.';
      });
  });

  // ── Close modal ─────────────────────────────────────────────────────────
  function closeModal() {
    overlay.hidden = true;
    document.body.style.overflow = '';
    stopPhotoPolling();
  }

  closeBtn.addEventListener('click', closeModal);
  overlay.addEventListener('click', e => { if (e.target === overlay) closeModal(); });
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

  // ── Use This Photo ──────────────────────────────────────────────────────
  usePhotoBtn.addEventListener('click', () => {
    thumbImg.src       = receivedUrl;
    thumbPreview.hidden = false;
    closeModal();
  });

  // ── Retake ──────────────────────────────────────────────────────────────
  retakeBtn.addEventListener('click', () => {
    thumbPreview.hidden = true;
    openBtn.click();   // triggers a fresh request
  });

  // ── Form submit ─────────────────────────────────────────────────────────
  document.getElementById('jewelry-form').addEventListener('submit', e => {
    e.preventDefault();
    const name     = document.getElementById('customer-name').value.trim();
    const pid      = document.getElementById('product-id').value.trim();
    const category = document.getElementById('category').value;
    if (!name || !pid || !category) {
      alert('Please fill in Customer Name, Product ID, and Category.');
      return;
    }
    if (!receivedUrl) {
      alert('Please upload a jewelry photo before submitting.');
      return;
    }
    alert('Entry submitted successfully!');
  });

  // Start connection polling immediately
  startConnectionPolling();
</script>

</body>
</html>
