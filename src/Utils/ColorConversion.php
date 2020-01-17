<?php

namespace Naomai\Utils;

class ColorConversion{
	// --- GIST BY zyphlar
	// https://gist.github.com/zyphlar/55dea0fae7914ff8eb4a

	// note: some of these are written with $this so they work inside classes. 
	// you could easily rewrite this to be a general function outside a class.
	// sorry for the inconsistent tabs
	// adapted from: http://www.actionscript.org/forums/showthread.php3?t=50746 via http://stackoverflow.com/questions/1773698/rgb-to-hsv-in-php

	// rgb2hsv
	// RGB Values:Number 0-255 
	// HSV Results:Number 0-1 
	public static function rgb2hsv($R, $G, $B){  		
	   $HSL = array(); 
	   $var_R = ColorConversion::clamp($R / 255, 0, 1); 
	   $var_G = ColorConversion::clamp($G / 255, 0, 1); 
	   $var_B = ColorConversion::clamp($B / 255, 0, 1); 
	   $var_Min = min($var_R, $var_G, $var_B); 
	   $var_Max = max($var_R, $var_G, $var_B); 
	   $del_Max = $var_Max - $var_Min; 
	   $V = $var_Max; 
	   if ($del_Max == 0){ 
		  $H = 0; 
		  $S = 0; 
	   } else { 
		  $S = $del_Max / $var_Max; 
		  $del_R = ( ( ( $var_Max - $var_R ) / 6 ) + ( $del_Max / 2 ) ) / $del_Max; 
		  $del_G = ( ( ( $var_Max - $var_G ) / 6 ) + ( $del_Max / 2 ) ) / $del_Max; 
		  $del_B = ( ( ( $var_Max - $var_B ) / 6 ) + ( $del_Max / 2 ) ) / $del_Max; 
		  if      ($var_R == $var_Max) $H = $del_B - $del_G; 
		  else if ($var_G == $var_Max) $H = ( 1 / 3 ) + $del_R - $del_B; 
		  else if ($var_B == $var_Max) $H = ( 2 / 3 ) + $del_G - $del_R; 
		  if ($H<0) $H++; 
		  if ($H>1) $H--; 
	   } 

	   return [$H, $S, $V]; 
	} 

	// hsv2rgb
	// HSV Values:Number 0-1 
	// RGB Results:Number 0-255 
	public static function hsv2rgb($H, $S, $V){ 		
		$RGB = array(); 
		$H=ColorConversion::clampCyclic($H,0,1);
		$S=ColorConversion::clamp($S,0,1);
		$V=ColorConversion::clamp($V,0,1);
		
		if($S == 0) { 
			$R = $G = $B = $V * 255; 
		} else { 
			$var_H = $H * 6; 
			$var_i = floor( $var_H ); 
			$var_1 = $V * ( 1 - $S ); 
			$var_2 = $V * ( 1 - $S * ( $var_H - $var_i ) ); 
			$var_3 = $V * ( 1 - $S * (1 - ( $var_H - $var_i ) ) ); 
			if       ($var_i == 0) { $var_R = $V     ; $var_G = $var_3  ; $var_B = $var_1 ; } 
			else if  ($var_i == 1) { $var_R = $var_2 ; $var_G = $V      ; $var_B = $var_1 ; } 
			else if  ($var_i == 2) { $var_R = $var_1 ; $var_G = $V      ; $var_B = $var_3 ; } 
			else if  ($var_i == 3) { $var_R = $var_1 ; $var_G = $var_2  ; $var_B = $V     ; } 
			else if  ($var_i == 4) { $var_R = $var_3 ; $var_G = $var_1  ; $var_B = $V     ; } 
			else                   { $var_R = $V     ; $var_G = $var_1  ; $var_B = $var_2 ; } 
			$R = $var_R * 255; 
			$G = $var_G * 255; 
			$B = $var_B * 255; 
		} 

		return [$R, $G, $B]; 
	} 
	// --- end of GIST BY zyphlar

	// hex2rgb
	// converts binary RGB value to array with indices [0=>r, 1=>g, 2=>b]
	public static function hex2rgb($hex){
		return [($hex>>16)&0xFF, ($hex>>8)&0xFF, $hex&0xFF];
	}

	// rgb2hex
	// converts color array (structured like above) into binary RGB value
	public static function rgb2hex($rgb){
		return ($rgb[0]<<16) | ($rgb[1]<<8) | ($rgb[2]);
	}
	
	// clamp
	// bounds value between given range
	public static function clamp($val, $min, $max){
		return max(min($val,$max),$min);
	}
	
	// clampCyclic
	// bounds cyclic value between given range (e.g. hue)
	// aka least positive remainder with offset
	public static function clampCyclic($val, $min, $max){
		$totalRange = ($max - $min);
		$remainder = fmod($val - $min, $totalRange);
		if($remainder < 0){
			$remainder += $totalRange;
		}
		$remainder += $min;
		return $remainder;
	}
	
	// util class stuff
	public function __construct(){
		throw new \Exception(get_class() . " is an utility class and should be used statically");
	}
}