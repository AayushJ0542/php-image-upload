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
  <title>Capture Jewelry Photo</title>
  <link rel="stylesheet" href="css/mobile.css">
</head>
<body>

  <div class="logo">💎</div>
  <h1>Jewelry Photo Capture</h1>
  <p class="sub">Open your rear camera and take a clear, well-lit photo of the piece.</p>

  <form id="upload-form" method="POST" action="upload.php" enctype="multipart/form-data">

    <input type="hidden" name="session" value="<?= htmlspecialchars($sessionId) ?>">

    <input
      type="file"
      id="file-input"
      name="jewelry_image"
      accept="image/*"
      capture="environment"
      required>

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

  <div class="session-note">Session: <?= htmlspecialchars($sessionId) ?></div>

  <!-- Result popup — shown after a successful upload -->
  <div class="result-overlay" id="result-overlay" hidden>
    <div class="result-modal">

      <!-- Top half: uploaded image -->
      <div class="result-top">
        <p class="result-label">Your Photo</p>
        <img id="result-img" src="" alt="Uploaded photo">
      </div>

      <!-- Bottom half: actions -->
      <div class="result-bottom">
        <div class="result-check">
          <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="none"
               viewBox="0 0 24 24" stroke="#34d399" stroke-width="2.2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
          </svg>
        </div>
        <h3>Photo Uploaded!</h3>
        <p>Your photo has been sent to the desktop. What would you like to do?</p>
        <div class="result-btns">
          <button class="btn-retry" id="retry-btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none"
                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round"
                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            Retry
          </button>
          <button class="btn-submit" id="submit-btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none"
                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
            Submit
          </button>
        </div>
      </div>

    </div>
  </div>

  <!-- Submitted confirmation (shown after Submit is tapped) -->
  <div class="submitted-overlay" id="submitted-overlay" hidden>
    <div class="submitted-card">
      <div class="submitted-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="52" height="52" fill="none"
             viewBox="0 0 24 24" stroke="#34d399" stroke-width="1.8">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
      </div>
      <h2>Photo Submitted!</h2>
      <p>Your jewelry photo has been confirmed and synced to the desktop.</p>
    </div>
  </div>

<script>
  const fileInput    = document.getElementById('file-input');
  const preview      = document.getElementById('preview');
  const uploadBtn    = document.getElementById('upload-btn');
  const feedback     = document.getElementById('feedback');
  const form         = document.getElementById('upload-form');
  const label        = document.getElementById('camera-label');
  const resultOverlay   = document.getElementById('result-overlay');
  const submittedOverlay = document.getElementById('submitted-overlay');
  const resultImg    = document.getElementById('result-img');
  const retryBtn     = document.getElementById('retry-btn');
  const submitBtn    = document.getElementById('submit-btn');

  let previewObjectUrl = null;

  fileInput.addEventListener('change', () => {
    const file = fileInput.files[0];
    if (!file) return;

    if (previewObjectUrl) URL.revokeObjectURL(previewObjectUrl);
    previewObjectUrl = URL.createObjectURL(file);

    preview.src           = previewObjectUrl;
    preview.style.display = 'block';
    uploadBtn.disabled    = false;
    label.querySelector('span').textContent = 'Change Photo';
    feedback.textContent  = '';
    feedback.className    = '';
  });

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!fileInput.files[0]) return;

    uploadBtn.disabled    = true;
    uploadBtn.textContent = 'Uploading…';
    feedback.textContent  = '';
    feedback.className    = '';

    try {
      const formData = new FormData(form);
      const res      = await fetch('upload.php', { method: 'POST', body: formData });
      const text     = await res.text();

      if (res.ok && text.includes('success')) {
        // Populate result popup with the captured preview
        resultImg.src = previewObjectUrl;
        resultOverlay.hidden = false;
        document.body.style.overflow = 'hidden';
      } else {
        throw new Error(text || 'Server error');
      }
    } catch (err) {
      feedback.textContent  = 'Upload failed: ' + err.message;
      feedback.className    = 'error';
      uploadBtn.disabled    = false;
      uploadBtn.textContent = 'Retry Upload';
    }
  });

  // Retry: close popup, reset form so user can pick a new photo
  retryBtn.addEventListener('click', () => {
    resultOverlay.hidden = true;
    document.body.style.overflow = '';
    // Reset form state
    form.reset();
    preview.style.display = 'none';
    preview.src = '';
    uploadBtn.disabled    = true;
    uploadBtn.textContent = 'Upload Photo';
    label.querySelector('span').textContent = 'Open Camera';
    feedback.textContent  = '';
    feedback.className    = '';
    if (previewObjectUrl) { URL.revokeObjectURL(previewObjectUrl); previewObjectUrl = null; }
  });

  // Submit: confirm and show final success screen
  submitBtn.addEventListener('click', () => {
    resultOverlay.hidden   = true;
    submittedOverlay.hidden = false;
  });
</script>

</body>
</html>
