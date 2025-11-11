<?
// -------------------------------------------------------------
// Screen detection helpers
// -------------------------------------------------------------
function detectScreenType() {
    $backlightDirs = glob('/sys/class/backlight/*');
    if (empty($backlightDirs)) {
        return "none";
    }

    foreach ($backlightDirs as $dir) {
        if (strpos($dir, '10-0045') !== false) {
            return "rpi_dsi";
        } elseif (strpos($dir, 'rpi_backlight') !== false) {
            return "rpi_hdmi";
        }
    }

    return "unknown";
}

function isScreenOn() {
    $dirs = glob('/sys/class/backlight/*');
    if (empty($dirs)) {
        return true; // assume ON if nothing to check
    }

    $file = $dirs[0] . '/bl_power';
    if (!file_exists($file)) {
        return true;
    }

    $val = trim(file_get_contents($file));
    // 0 = on, 1/4 = off
    return ($val === "0");
}


function returnIfExists($json, $setting) {
    if ($json == null) {
        return "";
    }
    if (array_key_exists($setting, $json)) {
        return $json[$setting];
    }
    return "";
}

function convertAndGetSettings() {
    global $settings;
        
    $cfgFile = $settings['configDirectory'] . "/plugin.fpp-BigButtons-screen-check";
    if (file_exists($cfgFile)) {
        $pluginSettings = parse_ini_file($cfgFile);
        $json = array();
        for ($x = 1; $x <= 20; $x++) {
            $buttonName = "button" . sprintf('%02d', $x);
            $color = returnIfExists($pluginSettings, $buttonName . "color");
            $desc = returnIfExists($pluginSettings, $buttonName . "desc");
            $script = returnIfExists($pluginSettings, $buttonName . "script");
            
            if ($color != "" || $desc != "" || $script != "") {
                $json["buttons"][$x]["description"] = $desc;
                $json["buttons"][$x]["color"] = $color;
                if ($script != "" && $script != null) {
                    $json["buttons"][$x]["command"] = "Run Script";
                    $json["buttons"][$x]["args"][] = $script;
                } else {
                    $json["buttons"][$x]["command"] = "";
                }
            }
        }
        $fontsize = returnIfExists($pluginSettings, "buttonFontSize");
        if ($fontsize != "" && $fontsize != null) {
            $json["fontSize"] = (int)$fontsize;
        }
        $title = returnIfExists($pluginSettings, "buttonTitle");
        if ($title != "" && $title != null) {
            $json["title"] = $title;
        }

        file_put_contents($cfgFile . ".json", json_encode($json, JSON_PRETTY_PRINT));
        unlink($cfgFile);
        return $json;
    }
    if (file_exists($cfgFile . ".json")) {
        $j = file_get_contents($cfgFile . ".json");
        $json = json_decode($j, true);
        return $json;
    }
    $j = "{\"fontSize\": 12, \"title\": \"\", \"buttons\": {\"1\": {}}}";
    return json_decode($j, true);
}


?>
