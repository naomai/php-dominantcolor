<?php

require_once "../vendor/autoload.php"; 

use Naomai\DominantColor;

header("Content-type: text/html; charset=utf-8");

printf("<h1>Dominant color test</h1>\n");
exampleImageSrc("DSC_1526.jpg");
exampleImageSrc("CRW_0026.jpg");
exampleImageSrc("CAM00018.jpg");



function exampleImageSrc($src){
	$colorInfo = DominantColor::fromFile($src,7);

	// --------

	printf("<h2>File %s:</h2>\n", htmlspecialchars($src));
	printf("<img src=\"%s\" alt=\"\" class=\"col_ex\" />\n", htmlspecialchars($src));
	
	printf("<div class=\"col_cont\">\n");
	$primary = $colorInfo['primary'];
	printf("<div style=\"background: #%06x; color: #%06x\" class=\"col_box\">Primary color</div>", $primary, 0xFFFFFF-$primary);

	$secondary = $colorInfo['secondary'];
	printf("<div style=\"background: #%06x; color: #%06x\" class=\"col_box\">Secondary color</div>", $secondary, 0xFFFFFF-$secondary);

	$palette = $colorInfo['palette'];
	printf("<h2>Other colors:</h2>\n", $src);
	foreach($palette as $paletteItem){
		$color = $paletteItem['color'];
		$score = $paletteItem['score'];
		printf("<div style=\"background: #%06x; color: #%06x\" class=\"col_box\">Score %.02f</div>", $color, 0xFFFFFF-$color, $score);
	}
	printf("</div>\n");
}

?>

<style> 
	.col_cont{
		display: inline-block;
		vertical-align: top;
	}
	.col_box{
		width: 64px;
		height: 64px;
		display: inline-block;
		text-align: center;
	}
	img.col_ex{
		width: 200px;
	}