<?php
// Einstiegspunkt für die Admin-Seite, Sicherheitsprüfung
require('includes/application_top.php');
if (!defined('IS_ADMIN_FLAG')) {
die('Illegal Access');
}
// Pfad zur Log-Datei, in der Bildverschiebungen gespeichert werden
$logfile = DIR_FS_CATALOG . 'logs/bildverschieber/bild_log.csv';
?>
<!doctype html>
<html <?php echo HTML_PARAMS; ?>>
  <head>
    <!-- Einbindung von Admin-Kopfbereich (z. B. CSS, JS) -->
    <?php require DIR_WS_INCLUDES . 'admin_html_head.php'; ?>
</head>
<body>
<?php require(DIR_WS_INCLUDES . 'header.php'); ?>
<div class="container-fluid">

<?php
// Funktion zum Erzeugen der Kategorie-Auswahl (rekursiv)
function getCategoryOptions($parent_id = 0, $depth = 0) {
    global $db;
    $options = '';
    $cats = $db->Execute("
        SELECT c.categories_id, cd.categories_name 
                          FROM " . TABLE_CATEGORIES . " c
                          JOIN " . TABLE_CATEGORIES_DESCRIPTION . " cd ON c.categories_id = cd.categories_id
                          WHERE c.parent_id = " . (int)$parent_id . " 
                          AND cd.language_id = 43
                          ORDER BY sort_order, cd.categories_name");
    while (!$cats->EOF) {
        $id = $cats->fields['categories_id'];
        $name = str_repeat('— ', $depth) . $cats->fields['categories_name'];
        $options .= "<option value=\"$id\">" . htmlspecialchars($name) . "</option>\n";
        $options .= getCategoryOptions($id, $depth + 1);
        $cats->MoveNext();
    }
    return $options;
}

// Funktion zur Ermittlung aller Unterkategorien rekursiv
function getAllSubcategories($parent_id, &$all = []) {
    global $db;
    $subcats = $db->Execute("SELECT categories_id FROM " . TABLE_CATEGORIES . " WHERE parent_id = " . (int)$parent_id);
    while (!$subcats->EOF) {
        $id = $subcats->fields['categories_id'];
        $all[] = $id;
        getAllSubcategories($id, $all); // rekursiver Aufruf
        $subcats->MoveNext();
    }
    return $all;
}

// Gibt alle Unterverzeichnisse im images/-Ordner zurück
function getImageSubfolders() {
    $base = DIR_FS_CATALOG . 'images/';
    $dirs = array_filter(glob($base . '*'), 'is_dir');
    $options = '';
    foreach ($dirs as $dir) {
        $name = str_replace($base, '', $dir);
        $options .= "<option value=\"$name\">$name</option>";
    }
    return $options;
}

// Funktion zur Rückabwicklung der letzten Bildverschiebung basierend auf Logfile
function undoChanges($logfile) {
    global $db;

    if (!file_exists($logfile)) {
        echo "<p>❌ Kein Logfile gefunden.</p>";
        return;
    }

    $lines = file($logfile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $header = array_shift($lines); // erste Zeile (Header) entfernen

    $count = 0;
    foreach ($lines as $line) {
        list($pid, $old, $new, $status) = explode(',', $line);

		if ($status === 'fehlt') continue;
        $src = DIR_FS_CATALOG . 'images/' . $new;
        $dest = DIR_FS_CATALOG . 'images/' . $old;

        if (file_exists($src)) {
            if (!is_dir(dirname($dest))) {
                mkdir(dirname($dest), 0777, true);
            }

            if (rename($src, $dest)) {
				// Nur bei Hauptbild (Status == "OK") auch DB aktualisieren
				if (trim($status) === 'OK') {
					$db->Execute("UPDATE " . TABLE_PRODUCTS . "
                              SET products_image = '" . zen_db_input($old) . "'
                              WHERE products_id = " . (int)$pid);
				}
                $count++;
            }
        }
    }

    echo "<p>♻️ <strong>$count Bilder erfolgreich zurückverschoben und Datenbank zurückgesetzt.</strong></p>";
}

// Rückgängig machen, wenn undo=1 per GET übergeben wurde
if (isset($_GET['undo'])) {
    echo '<h1>🔁 Undo Bildverschiebung</h1>';
	echo "<pre>🛠️ Undo wurde aufgerufen</pre>";
    undoChanges($logfile);
    echo '<p><a href="' . zen_href_link('index.php', 'cmd=bildverschieber') . '">🔙 Zurück</a></p>';
    require('includes/application_bottom.php');
    exit;
}

// Formular anzeigen, wenn nichts gepostet wurde
echo '<h1>🔁 Bildverschieber: Bilder verschieben & DB aktualisieren</h1>';

if (!isset($_POST['category_id'])) {
    echo '
    <form method="post">
        <label>Kategorie wählen:
            <select name="category_id" required>
                <option value="">-- Kategorie wählen --</option>
                ' . getCategoryOptions() . '
            </select>
        </label><br><br>

        <label>Zielordner (unter /images):
            <select name="target_subdir" required>
                <option value="">-- Zielordner wählen --</option>
                ' . getImageSubfolders() . '
            </select>
        </label><br><br>

        <input type="submit" value="Starte Bildverschiebung">
    </form>';

    // Undo-Button, falls Logfile existiert
    if (file_exists($logfile)) {
        echo '<hr><form method="get" action="index.php">
			<input type="hidden" name="cmd" value="bildverschieber">
            <input type="hidden" name="undo" value="1">
            <input type="submit" value="🔁 Letzte Aktion rückgängig machen">
        </form>';
    }

    require('includes/application_bottom.php');
    exit;
}

// Verarbeitung des Formulars nach POST
$main_category_id = (int)$_POST['category_id'];
$target_subdir = rtrim($_POST['target_subdir'], '/') . '/';
$target_dir = DIR_FS_CATALOG . 'images/' . $target_subdir;
$csv_log = $logfile;

// Alle Unterkategorien sammeln
$category_ids = [$main_category_id];
getAllSubcategories($main_category_id, $category_ids);
$category_list_sql = implode(',', array_map('intval', $category_ids));

// Produkte mit Bildern aus der Kategorie holen
$sql = "SELECT DISTINCT p.products_id, p.products_image
        FROM " . TABLE_PRODUCTS . " p
        JOIN " . TABLE_PRODUCTS_TO_CATEGORIES . " ptc ON p.products_id = ptc.products_id
        WHERE ptc.categories_id IN ($category_list_sql)
        AND p.products_image IS NOT NULL
        AND p.products_image != ''";

$products = $db->Execute($sql);
$csv_lines = [];
$csv_lines[] = "products_id,old_image_path,new_image_path,status";

$count = 0;
$errors = 0;

// Hauptverarbeitung: Bilder verschieben
while (!$products->EOF) {
    $id = $products->fields['products_id'];
    $image = $products->fields['products_image'];
    $src = DIR_FS_CATALOG . 'images/' . $image;
    $filename = basename($image);
    $new_image = $target_subdir . $filename;
    $dest = $target_dir . $filename;

    if (!file_exists($src)) {
        $csv_lines[] = "$id,$image,NICHT GEFUNDEN,fehlt";
        $errors++;
    } else {
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        if (rename($src, $dest)) {
            // Datenbank aktualisieren
            $db->Execute("UPDATE " . TABLE_PRODUCTS . "
                          SET products_image = '" . zen_db_input($new_image) . "'
                          WHERE products_id = " . (int)$id);
            $csv_lines[] = "$id,$image,$new_image,OK";
            // Weitere Bildvarianten wie _01, _001 etc. verschieben
            $base_name = preg_replace('/\\.[^.]+$/', '', $filename);
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $image_dir = DIR_FS_CATALOG . 'images/';
            $patterns = [$base_name . '_*.' . $extension, $base_name . '-*.' . $extension];

            foreach ($patterns as $pattern) {
                foreach (glob($image_dir . $pattern) as $extra_src) {
                    $extra_name = basename($extra_src);
                    $extra_dest = $target_dir . $extra_name;
                    $extra_new_image = str_replace(DIR_FS_CATALOG . 'images/', '', $extra_dest);

                    if (!file_exists($extra_dest)) {
                        if (rename($extra_src, $extra_dest)) {
                            $csv_lines[] = "$id,$extra_name,$extra_new_image,OK (zusätzlich)";
                        } else {
                            $csv_lines[] = "$id,$extra_name,$extra_new_image,FEHLER (zusätzlich)";
                        }
                    }
                }
            }
            $count++;
        } else {
            $csv_lines[] = "$id,$image,$new_image,FEHLER";
            $errors++;
        }
    }

    $products->MoveNext();
}

// CSV-Log schreiben
file_put_contents($csv_log, implode("\n", $csv_lines));

// Erfolgsausgabe
echo "<p>✅ <strong>$count Bilder verschoben und Datenbank aktualisiert.</strong></p>";
echo "<p>⚠️ <strong>$errors Fehler oder fehlende Bilder.</strong></p>";
echo "<p><a href='../logs/bildverschieber/bild_log.csv' target='_blank'>📥 CSV-Log herunterladen</a></p>";
?>

<?php require DIR_WS_INCLUDES . 'footer.php'; ?>
</body>
</html>
<?php require('includes/application_bottom.php');?>