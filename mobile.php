<?php
$sessionId = trim($_GET['session'] ?? '');

if (!preg_match('/^[a-zA-Z0-9_.]+$/', $sessionId)) {
    http_response_code(400);
    die('Invalid session.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>Jewelry Photo Capture</title>
  <link rel="stylesheet" href="css/mobile.css">
</head>
<body>

  <!-- STATE: connecting -->
  <div class="state" id="state-connecting">
    <div class="logo">💎</div>
    <h1>Connecting…</h1>
    <div class="spinner"></div>
  </div>

  <!-- STATE: standby — waiting for a desktop request -->
  <div class="state" id="state-standby" hidden>
    <div class="logo">💎</div>
    <h1>Phone Connected</h1>
    <p class="sub">Waiting for desktop to request a photo…</p>
    <div class="standby-dot-row">
      <span class="standby-dot"></span>
      <span id="standby-text">Listening for requests</span>
    </div>
    <div class="session-note">Session: <?= htmlspecialchars($sessionId) ?></div>
  </div>

  <!-- STATE: capture — desktop has requested a photo -->
  <div class="state" id="state-capture" hidden>
    <div class="logo pulse-logo">📸</div>
    <h1>Photo Requested!</h1>
    <p class="sub">Take a clear, well-lit photo of the jewelry piece.</p>

    <form id="upload-form" method="POST" action="upload.php" enctype="multipart/form-data">
      <input type="hidden" name="session"    value="<?= htmlspecialchars($sessionId) ?>">
      <input type="hidden" name="request_id" id="request-id-input" value="">

      <input type="file" id="file-input" name="jewelry_image"
             accept="image/*" capture="environment" required>

      <label for="file-input" class="camera-label" id="camera-label">
        <svg xmlns="http://www.w3.org/2000/svg" width="52" height="52" fill="none"
             viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.4">
          <path stroke-linecap="round" stroke-linejoin="round"
            d="M3 7h2l2-3h10l2 3h2a1 1 0 011 1v11a1 1 0 01-1 1H3a1 1 0 01-1-1V8a1 1 0 011-1z"/>
          <circle cx="12" cy="13" r="3.5"/>
        </svg>
        <span>Open Camera</span>
        <small>Tap to take or choose a photo</small>
      </label>

      <img id="preview" src="" alt="Preview">

      <button type="submit" class="upload-btn" id="upload-btn" disabled>
        Upload Photo
      </button>

      <div id="feedback"></div>
    </form>
  </div>

  <!-- STATE: uploading -->
  <div class="state" id="state-uploading" hidden>
    <div class="logo">💎</div>
    <h1>Uploading…</h1>
    <div class="spinner"></div>
    <p class="sub">Sending photo to desktop…</p>
  </div>

  <!-- STATE: sent — photo delivered, return to standby -->
  <div class="state" id="state-sent" hidden>
    <div class="logo">✅</div>
    <h1>Photo Sent!</h1>

    <!-- Result popup half: uploaded image -->
    <div class="result-top">
      <p class="result-label">Your Photo</p>
      <img id="result-img" src="" alt="Uploaded photo">
    </div>

    <div class="result-bottom">
      <p>Photo delivered to the desktop.</p>
      <div class="result-btns">
        <button class="btn-retry" id="retry-btn">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none"
               viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
          </svg>
          Retake
        </button>
        <button class="btn-submit" id="confirm-btn">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none"
               viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
          </svg>
          Looks Good
        </button>
      </div>
    </div>
  </div>

  <!-- STATE: confirmed — waiting for next request -->
  <div class="state" id="state-confirmed" hidden>
    <div class="logo">💎</div>
    <h1>All Done!</h1>
    <p class="sub">Photo confirmed. Waiting for the next request…</p>
    <div class="standby-dot-row">
      <span class="standby-dot"></span>
      <span>Listening for requests</span>
    </div>
  </div>

<script>
  const SESSION  = <?= json_encode($sessionId) ?>;
  const POLL_MS  = 2000;

  const states = {
    connecting : document.getElementById('state-connecting'),
    standby    : document.getElementById('state-standby'),
    capture    : document.getElementById('state-capture'),
    uploading  : document.getElementById('state-uploading'),
    sent       : document.getElementById('state-sent'),
    confirmed  : document.getElementById('state-confirmed'),
  };

  const fileInput      = document.getElementById('file-input');
  const preview        = document.getElementById('preview');
  const uploadBtn      = document.getElementById('upload-btn');
  const feedback       = document.getElementById('feedback');
  const form           = document.getElementById('upload-form');
  const cameraLabel    = document.getElementById('camera-label');
  const reqIdInput     = document.getElementById('request-id-input');
  const resultImg      = document.getElementById('result-img');
  const retryBtn       = document.getElementById('retry-btn');
  const confirmBtn     = document.getElementById('confirm-btn');

  let pollTimer        = null;
  let previewObjectUrl = null;
  let activeRequestId  = 0;

  // ── helpers ───────────────────────────────────────────────────────────
  function showState(name) {
    Object.entries(states).forEach(([k, el]) => { el.hidden = k !== name; });
  }

  function startPolling() {
    pollTimer = setInterval(pollRequests, POLL_MS);
    pollRequests();
  }

  function stopPolling() {
    if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
  }

  // ── 1. Connect (register phone) ───────────────────────────────────────
  showState('connecting');

  fetch('connect.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'session=' + encodeURIComponent(SESSION),
  })
    .then(r => r.ok ? r.json() : Promise.reject())
    .then(() => {
      showState('standby');
      startPolling();
    })
    .catch(() => {
      // Retry connect after 3s
      setTimeout(() => location.reload(), 3000);
    });

  // ── 2. Poll for pending requests ──────────────────────────────────────
  function pollRequests() {
    fetch('mobile-poll.php?session=' + encodeURIComponent(SESSION))
      .then(r => r.ok ? r.json() : Promise.reject())
      .then(data => {
        const reqId = parseInt(data.pending_request_id, 10);
        if (reqId > 0 && reqId !== activeRequestId) {
          activeRequestId = reqId;
          stopPolling();
          onRequestReceived(reqId);
        }
      })
      .catch(() => {});
  }

  // ── 3. Request received — show capture UI ─────────────────────────────
  function onRequestReceived(reqId) {
    reqIdInput.value = reqId;
    // Vibrate if supported
    if (navigator.vibrate) navigator.vibrate([200, 100, 200]);
    resetCaptureUI();
    showState('capture');
  }

  function resetCaptureUI() {
    form.reset();
    preview.style.display = 'none';
    preview.src = '';
    uploadBtn.disabled = true;
    uploadBtn.textContent = 'Upload Photo';
    cameraLabel.querySelector('span').textContent = 'Open Camera';
    feedback.textContent = '';
    feedback.className = '';
    if (previewObjectUrl) { URL.revokeObjectURL(previewObjectUrl); previewObjectUrl = null; }
  }

  // ── 4. File selected — show preview ───────────────────────────────────
  fileInput.addEventListener('change', () => {
    const file = fileInput.files[0];
    if (!file) return;
    if (previewObjectUrl) URL.revokeObjectURL(previewObjectUrl);
    previewObjectUrl = URL.createObjectURL(file);
    preview.src           = previewObjectUrl;
    preview.style.display = 'block';
    uploadBtn.disabled    = false;
    cameraLabel.querySelector('span').textContent = 'Change Photo';
    feedback.textContent  = '';
    feedback.className    = '';
  });

  // ── 5. Upload ─────────────────────────────────────────────────────────
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!fileInput.files[0]) return;

    showState('uploading');

    try {
      const formData = new FormData(form);
      const res      = await fetch('upload.php', { method: 'POST', body: formData });
      const text     = await res.text();

      if (res.ok && text.includes('success')) {
        resultImg.src = previewObjectUrl;
        showState('sent');
      } else {
        throw new Error(text || 'Server error');
      }
    } catch (err) {
      showState('capture');
      feedback.textContent = 'Upload failed: ' + err.message;
      feedback.className   = 'error';
      uploadBtn.disabled   = false;
      uploadBtn.textContent = 'Retry Upload';
    }
  });

  // ── 6. Retry — retake the photo for the same request ─────────────────
  retryBtn.addEventListener('click', () => {
    resetCaptureUI();
    showState('capture');
  });

  // ── 7. Confirm — go back to standby for next request ─────────────────
  confirmBtn.addEventListener('click', () => {
    showState('confirmed');
    activeRequestId = 0;
    // Resume polling for next desktop request
    startPolling();
    // Transition text back to standby after 2s
    setTimeout(() => showState('standby'), 2000);
  });
</script>

</body>
</html>
