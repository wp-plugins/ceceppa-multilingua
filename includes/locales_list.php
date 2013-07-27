<?php

gloabl $wpCeceppaML;

//Non posso richiamare lo script direttamente dal browser :)
if(!is_object($wpCeceppaML)) die("Access denied");

//Genero il file locales_code.php
$handle  = fopen(WP_PLUGIN_DIR . "/ceceppa-multilingua/locales_list.txt", 'r');
$out = fopen(WP_PLUGIN_DIR . "/ceceppa-multilingua/locales_codes.php", 'w');
fwrite($out, "<?php \n");
fwrite($out, '$_langs = array(');

//Scorro le righe
while (!feof($handle)) {
	$line = split(":", fgets($handle));
	
	$l .= "\n" . '"' . trim($line[0]) . '" => "' . trim($line[1]) . '",';
}
fwrite($out, substr($l, 0, -1) . ");");
fwrite($out, "\n ?>");

fclose($out);
fclose($handle);
?>