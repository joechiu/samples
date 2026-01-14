
<?
# debug if need
# error_reporting(E_ALL);
# ini_set('display_errors', 1);

$url     = $_SERVER['REQUEST_URI'];
$updir   = "";
$basedir = "";
$p       = (object) $_POST;

$msg = "";

if ($p->path) {

  $newdir = preg_replace('/\s+$/', '', $p->newdir);
  /* Full path */
  $updir = "$basedir/$p->path/$newdir";

  /* Method 1: Try to change directory (BEST way) */
  if (is_dir($updir)) {
      $msg .= "<font color=green>Directory already exists: $updir</font><br>";
  } else {
    /* Does not exist → create */
    if (mkdir($updir, 0775, true)) {
      $msg .= "<font color=green>New directory created: $updir</font><br>";
    } else {
      $msg .= "<font color=red>Failed to create directory: $updir</font><br>";
    }
  }

  $count = count($_FILES['userfile']['name']);
  $c = 0;
  $done = [];

  foreach ($_FILES['userfile']['name'] as $i => $name) {
    $c++;
    if ($_FILES['userfile']['error'][$i] !== UPLOAD_ERR_OK) {
      echo "Upload error code: " . $_FILES['userfile']['error'][$i] . "<br>";
      continue;
    }
    $tmp = $_FILES['userfile']['tmp_name'][$i];
    /* Sanitize filename */
    $name = basename($name);
    $remote_file = $updir."/".$name;

    if (in_array($name, $done)) {
      sleep(1);
      $filename = time().'_'.$name;
      $msg .= "<font color=green>Warning: $name is existing, renamed to $filename!</font><br>\n";
      $remote_file = "$updir/$filename";
    } else {
      array_push($done, $name);
    }

    /* Debug */
    $msg .= "<b>$c/$count. Uploading to: $remote_file</b><br>";

    if (move_uploaded_file($tmp, $remote_file)) {
      $msg .= "✅ <font color=blue>$name uploaded successfully via FTP!</font><br>";
    } else {
      $msg .= "❌ <font color=red>Error uploading $name via FTP.</font><br>";
      $msg .= "<font color=red>FTP error code: " . ftp_errno($conn) . "</font><br>";
    }

    clone_exif($remote_file);

  }
}
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
<link rel="stylesheet" type="text/css" href="/up/css/up.css" />
<script src="/up/js/up.js" type="text/javascript"></script>
</head>
<body>
<a name=top>
<form class=butt enctype="multipart/form-data" method="post" onsubmit="show('ok');">
<select name="path" required id=title style="border: 4px solid #336699;">
  <option value="">-- Select Target Directory for Upload --</option>
  <? foreach ($dirs as $d): ?>
    <option value="<?= htmlspecialchars($d) ?>">
      <?= htmlspecialchars($d) ?>
    </option>
  <? endforeach; ?>
</select>
<br>
<a id=title>To Folder:</a><input name="newdir" size=18 border=3 id=title style="border: 4px solid #336699;">
<br>
<input name="userfile[]" type="file" multiple>
<br>
<input type="submit" value="Upload" id=foo>
<button onclick="location.href = '<?=$url?>'" id=foo>Refresh</button>
<br>
</form>

<? if ($count) { ?>
<a href="#end"><button id=title>END</button></a>
<table class=box>
<tr>
  <td nowrap><?=$msg?></td>
</tr>
</table>

<a name=end>
<a href="#top"><button id=title>TOP</button></a>
<? } else { ?>
<div class="spinner-overlay hidden" id="spinner">
  <div class="spinner"></div>
</div>
<? } ?>
</body>
</html>

<?
# touch file time stat back to itself
function clone_exif($file)
{
  if (!file_exists($file)) {
    return false;
  }

  if (!function_exists('exif_read_data')) {
    return false;
  }

  $exif = @exif_read_data($file);
  if ($exif === false) {
    return false;
  }

  /* Manually check EXIF fields (PHP 5.6 compatible) */
  if (!empty($exif['DateTimeOriginal'])) {
    $date = $exif['DateTimeOriginal'];
  } elseif (!empty($exif['CreateDate'])) {
    $date = $exif['CreateDate'];
  } elseif (!empty($exif['DateTime'])) {
    $date = $exif['DateTime'];
  } else {
    return false;
  }

  /* Convert EXIF format YYYY:MM:DD HH:MM:SS */
  $ts = strtotime(
    str_replace(':', '-', substr($date, 0, 10)) . substr($date, 10)
  );

  if ($ts === false) {
    return false;
  }

  return touch($file, $ts);
}

?>

