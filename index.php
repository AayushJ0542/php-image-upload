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
$qrApiUrl  = 'https://api.qrserver.com/v1/create-qr-code/?size=240x240&data=' . urlencode($mobileUrl);
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

      <!-- Upload Image button -->
      <div class="form-row">
        <button type="button" class="upload-image-btn" id="open-modal">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none"
               viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M3 7h2l2-3h10l2 3h2a1 1 0 011 1v11a1 1 0 01-1 1H3a1 1 0 01-1-1V8a1 1 0 011-1z"/>
            <circle cx="12" cy="13" r="3.5"/>
          </svg>
          Upload Image via Phone
        </button>

        <!-- Thumbnail shown after photo received -->
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

<!-- QR / Photo popup -->
<div class="modal-overlay" id="modal-overlay" hidden>
  <div class="modal">
    <button class="modal-close" id="modal-close" title="Close">&times;</button>

    <!-- Left: QR code -->
    <div class="modal-qr">
      <h2>Scan with your phone</h2>
      <img src="<?= htmlspecialchars($qrApiUrl) ?>" alt="QR Code" width="220" height="220">
      <div class="session-badge">Session: <?= htmlspecialchars($sessionId) ?></div>
      <div class="status-row">
        <span class="dot" id="status-dot"></span>
        <span id="status-text">Waiting for photo&hellip;</span>
      </div>
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
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
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
  const SESSION  = <?= json_encode($sessionId) ?>;
  const INTERVAL = 2000;

  const overlay        = document.getElementById('modal-overlay');
  const openBtn        = document.getElementById('open-modal');
  const closeBtn       = document.getElementById('modal-close');
  const dot            = document.getElementById('status-dot');
  const statusText     = document.getElementById('status-text');
  const placeholder    = document.getElementById('image-placeholder');
  const imgEl          = document.getElementById('jewelry-image');
  const imgActions     = document.getElementById('modal-image-actions');
  const dlBtn          = document.getElementById('download-btn');
  const usePhotoBtn    = document.getElementById('use-photo-btn');
  const thumbPreview   = document.getElementById('thumb-preview');
  const thumbImg       = document.getElementById('thumb-img');
  const retakeBtn      = document.getElementById('retake-btn');

  let timer        = null;
  let receivedUrl  = null;

  function startPolling() {
    poll();
    timer = setInterval(poll, INTERVAL);
  }

  function stopPolling() {
    if (timer) { clearInterval(timer); timer = null; }
  }

  function showReady(url) {
    receivedUrl = url;
    dot.className          = 'dot ready';
    statusText.textContent = 'Photo received!';
    imgEl.src              = url;
    imgEl.style.display    = 'block';
    placeholder.style.display = 'none';
    dlBtn.href             = url;
    imgActions.hidden      = false;
    stopPolling();
  }

  function poll() {
    fetch('status.php?session=' + encodeURIComponent(SESSION))
      .then(r => r.ok ? r.json() : Promise.reject())
      .then(data => { if (data.status === 'ready') showReady(data.url); })
      .catch(() => {
        dot.className          = 'dot error';
        statusText.textContent = 'Connection error — retrying…';
        setTimeout(() => {
          dot.className          = 'dot';
          statusText.textContent = 'Waiting for photo…';
        }, 3000);
      });
  }

  openBtn.addEventListener('click', () => {
    overlay.hidden = false;
    document.body.style.overflow = 'hidden';
    if (!receivedUrl) startPolling();
  });

  function closeModal() {
    overlay.hidden = true;
    document.body.style.overflow = '';
    stopPolling();
  }

  closeBtn.addEventListener('click', closeModal);
  overlay.addEventListener('click', e => { if (e.target === overlay) closeModal(); });
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

  // "Use This Photo" — attach thumbnail to form and close modal
  usePhotoBtn.addEventListener('click', () => {
    thumbImg.src       = receivedUrl;
    thumbPreview.hidden = false;
    openBtn.style.display = 'none';
    closeModal();
  });

  // "Retake" — reopen the modal
  retakeBtn.addEventListener('click', () => {
    thumbPreview.hidden = true;
    openBtn.style.display = '';
    // Reset modal state
    receivedUrl = null;
    imgEl.src = '';
    imgEl.style.display = 'none';
    placeholder.style.display = '';
    imgActions.hidden = true;
    dot.className = 'dot';
    statusText.textContent = 'Waiting for photo\u2026';
    overlay.hidden = false;
    document.body.style.overflow = 'hidden';
    startPolling();
  });

  // Basic form validation on submit
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
</script>

</body>
</html>
