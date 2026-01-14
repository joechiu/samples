<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$p = (object) $_POST;
$s = [
    "up" => "ok",
    "name" => "",
    "err" => "",
    "msg" => ""
];

// Upload target folder
$target = "/var/www/html/bump/files";
if (!empty($p->path)) {
    $target .= "/" . $p->path;
    if (!empty($p->dir)) {
        $dir = trim($p->dir);
        $target .= "/" . $dir;
    }
}

// Make sure the folder exists
if (!is_dir($target) && !mkdir($target, 0777, true)) {
    $s["up"] = "no";
    $s["msg"] = "Failed to create folder: $target";
    exit(json_encode($s));
}

// Ensure Apache user can write
chown($target, 'www-data');
chmod($target, 0777);

// Check files
if (empty($_FILES['userfile']['name'])) {
    $s["up"] = "no";
    $s["err"] = "No files uploaded.";
    exit(json_encode($s));
}

// Loop through files
foreach ($_FILES['userfile']['tmp_name'] as $i => $tmp) {
    $name = basename($_FILES['userfile']['name'][$i]);
    // Handle duplicate filenames
    if (is_file("$target/$name")) {
        $n = $name;
        $name = time() . "_" . $name;
        $s["msg"] = "File $n renamed to $name";  // override previous message
    }
    $s["name"] = $name;

    $dest = "$target/$name";

    if (!is_uploaded_file($tmp)) {
        $s["up"] = "no";
        $s["err"] = "Failed to upload $name (not uploaded)";
        continue;
    }

    if (!move_uploaded_file($tmp, $dest)) {
        $s["up"] = "no";
    } else {
        $s["up"] = "ok";
        $s["err"] = "Uploaded $name successfully";  // override previous message
        clone_exif($dest);
    }
}

// Return JSON
exit(json_encode($s));

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

