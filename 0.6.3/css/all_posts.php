<?php header("Content-type: text/css", true); ?>
<?php
  $count = intval($_GET['langs']);
?>

/* Bandiere in "Tutti gli articoli" */
#cml_flags, .cml_flags {
	width: <?php echo $count * 35 ?>px;
	text-align: center;
	white-space:nowrap;
	padding: 0;
	margin: 0;
}

#cml_flags img, .column-cml_flags img {
  padding-right: 10px;
}

.cml_flags {
  width: inehirt;
}
.cml_flags img {
  padding: 0 5px 0 5px;
  width: 20px;
}
.cml_flags img.add {
  padding: 0 10px 0 1px;
  width: 8px;
}