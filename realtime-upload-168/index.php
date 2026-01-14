<?
$basedir = "/var/www/html/bump/files";
/* List directories */
$dirs = [];
foreach (scandir($basedir) as $item) {
  if ($item === '.' || $item === '..' || $item[0] === '.') continue;
  if (is_dir($basedir . '/' . $item)) {
    $dirs[] = $item;
  }
}
?>
<html>
<title>Upload Page - 168</title>
<head>
<style>
body {
  margin: 36px;
  font: normal 36px Verdana, Arial, sans-serif;
  font-size: 36px;
}
</style>
<link rel="stylesheet" type="text/css" href="/bump/css/up.css" />
<script src="/bump/js/up.js" type="text/javascript"></script>
</head>
<body>
<a name=top>
<select id="path" required class=title style="border: 4px solid #336699;">
  <option value="">-- Select Target Directory for Upload --</option>
  <? foreach ($dirs as $d): ?>
    <option value="<?= htmlspecialchars($d) ?>">
      <?= htmlspecialchars($d) ?>
    </option>
  <? endforeach; ?>
</select>
<br>
Dir: <input type="text" id="dir" class=title value="" placeholder="New Folder"><br>
<input type="file" id="files" multiple><br>
<button onclick="startUpload()" class=foo>Upload</button><br>
<a href="#end"><button class=title>END</button></a>

<div id="status"></div>

<a name=end>
<a href="#top"><button class=title>TOP</button></a>

<!-- Spinner -->
<div id="spinner" class="spinner-overlay hidden">
  <div class="spinner"></div>
</div>

<script>
const BATCH_SIZE = 1;

const spinner = document.getElementById('spinner');
const status  = document.getElementById('status');

function showSpinner(show) {
  spinner.classList.toggle('hidden', !show);
}

function updateStat(html) {
  status.insertAdjacentHTML('beforeend', html);
  status.scrollTop = status.scrollHeight;
}

async function startUpload() {
  const input = document.getElementById('files');
  const files = input.files;

  // Get the values from your text inputs
  const path = document.getElementById('path').value; // <input id="path">
  const dir  = document.getElementById('dir').value;  // <input id="dir">

  if (!files.length) {
    alert("Please select files first");
    return;
  }

  showSpinner(true);
  status.innerHTML = "";

  let no = 0;
  let count = files.length;

  try {
    for (let i = 0; i < files.length; i += BATCH_SIZE) {
      no++;

      const chunk = Array.from(files).slice(i, i + BATCH_SIZE);
      const formData = new FormData();

      chunk.forEach(file => {
        formData.append('userfile[]', file);
      });

      // Add extra fields
      formData.append('path', path);
      formData.append('dir', dir);

      // Corrected: store fetch result in a variable
      const response = await fetch('./upload.php', { method: 'POST', body: formData });
      const stat = await response.json();

      if (stat.msg != "") updateStat(`<font color=green>${stat.msg}</font><br>`);
      if (stat.up == "ok") updateStat(`<font color=blue>✅ ${no}/${count}. file ${stat.name} uploaded.</font><br>`);
      else updateStat(`<font color=red>❌ ${no}/${count}. file ${stat.name} cannot be uploaded. ${stat.msg}</font><br>`);
      if (stat.err != "") updateStat(`<font color=red>${stat.msg}</font><br>`);

      // small delay helps WD NAS + iOS Safari
      await new Promise(r => setTimeout(r, 300));
    }

    updateStat("✅ All files uploaded successfully");
  } catch (e) {
    updateStat("❌ Upload failed: " + e.message);
  } finally {
    showSpinner(false);
  }
}
</script>

</body>
</html>
