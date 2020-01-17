<?php
namespace Naomai;

use Naomai\Utils\ColorConversion;
use KMeans\Space;

class DominantColor{

	/* define how many pixels horizontally and vertically are taken into account 
		100x100 is more than enough to get colors from album covers
	*/
	const CalcWidth = 100;
	const CalcHeight = 100;
	

	public static function fromGD($gdImage, int $colorCount = 2){
		$colorCount = max($colorCount, 2); // at least 2 colors - primary and secondary
		
		$space = self::imageToKSpace($gdImage);
		
		$clusters = $space->solve($colorCount, Space::SEED_DASV);

		/* score calculation for primary and secondary dominant color */
		$scores = self::createScoreArray($clusters);
		$primary = self::findPrimaryColor($scores);
		$secondary = self::findSecondaryColor($scores);

		/* //display colors with their scores
		foreach($scores['clusters'] as &$c){
			printf("<span style='background: #%06x'>Cluster %d points p_score=%.02f s_score=%.02f [S=%.02f,V=%.02f]</span><br/>\n", $c['color'], $c['count'], $c['p_score'], $c['s_score'], $c['s'], $c['v']);
		}*/
				
		$palette = [];
		foreach($scores['clusters'] as &$c){
			if($c['color'] != $primary['color'] && $c['color'] != $secondary['color']){
				$palette[] = ['color'=>$c['color'],'score'=>$c['s_score'] / $scores['secondary']['maxScore']];
			}
		}
		usort($palette, function($a,$b){
			return $b['score'] <=> $a['score'];
		});

		return ["primary"=>$primary['color'], "secondary"=>$secondary['color'], "palette"=>$palette];
	}
	
	public static function fromFile(string $fileName, int $colorCount = 2){
		$gdImg = imagecreatefromstring(file_get_contents($fileName));
		$colorInfo = DominantColor::fromGD($gdImg, $colorCount);
		imagedestroy($gdImg);
		return $colorInfo;
	}
	
	private static function imageToKSpace($gdImage){
		$wImage = imagesx($gdImage);
		$hImage = imagesy($gdImage);

		$xSkip = max($wImage / DominantColor::CalcWidth, 1);
		$ySkip = max($hImage / DominantColor::CalcHeight, 1);

		$space = new Space(3);

		// walk through the pixels
		for($y=0; $y<$hImage; $y+=$ySkip){
			for($x=0; $x<$wImage; $x+=$xSkip){
				$xRGB = imagecolorat($gdImage, floor($x), floor($y));
				$aRGB = ColorConversion::hex2rgb($xRGB);
				$aHSV = ColorConversion::rgb2hsv($aRGB[0], $aRGB[1], $aRGB[2]);
				// convert HSV to coordinates in cone
				$pr = $aHSV[1] * $aHSV[2]; // radius
				
				$px = sin($aHSV[0] * 2 * M_PI) * $pr;
				$py = cos($aHSV[0] * 2 * M_PI) * $pr;
				$pz = $aHSV[2]/3;
				
				$space->addPoint([$px, $py, $pz], [$aHSV, $xRGB]);
			}
		}
		return $space;
	}
	
	private static function createScoreArray(array $clusters){
		$clusterScore = [];
		$maxCount = 0;
		$maxS = 0;
		$maxV = 0;

		foreach ($clusters as $i => $cluster){
			if(!count($cluster)) continue;
			$closest = $cluster->getClosest($cluster);

			$colors = $closest->toArray()['data'];
			$aHSV = $colors[0];
			$xRGB = $colors[1];
			$clusterCount = count($cluster);
			
			$clusterScore[] = [ "clusterObj"=>$cluster, "color"=>$xRGB, 
				"h"=>$aHSV[0], "s"=>$aHSV[1], "v"=>$aHSV[2], 
				"count"=>$clusterCount ];
			
			$maxCount = max($maxCount, $clusterCount);
			$maxS = max($maxS, $aHSV[1]);
			$maxV = max($maxV, $aHSV[2]);
		}
		
		if(!$maxS) $maxS = 1;
		if(!$maxV) $maxV = 1;
		return ['clusters'=>$clusterScore, 'maxCount'=>$maxCount, 'maxS'=>$maxS, 'maxV'=>$maxV];
	}
	private static function findPrimaryColor(array &$scoreArray){
		foreach($scoreArray['clusters'] as &$c){
			$sf = $c['s'] / $scoreArray['maxS'];
			$vf = $c['v'] / $scoreArray['maxV'];
			$cf = $c['count'] / $scoreArray['maxCount'];
			$scorePrimary = 5*$sf + 5*$vf + 1*$cf;
			$c['p_score'] = $scorePrimary;
		}
		$maxPScore = 0;
		$primaryIdx = 0;

		array_walk($scoreArray['clusters'], function($c, $idx)use(&$maxPScore,&$primaryIdx){
			if($c['p_score'] > $maxPScore){
				$maxPScore = $c['p_score'];
				$primaryIdx = $idx;
			}
		});
		$scoreArray['primary'] = ['maxScore'=>$maxPScore, 'idx'=>$primaryIdx];
		return $scoreArray['clusters'][$primaryIdx];
	}
	private static function findSecondaryColor(array &$scoreArray){
		$maxSScore = 0;
		$secondaryIdx = 0;
		
		$primary = $scoreArray['clusters'][$scoreArray['primary']['idx']];
		
		array_walk($scoreArray['clusters'], function(&$c, $idx)
			use(&$maxSScore,&$secondaryIdx,$scoreArray,$primary){
				
			if($idx==$scoreArray['primary']['idx']) { // primary != secondary
				$c['s_score']=0;
				return;
			}
			$sf = $c['s'] / $scoreArray['maxS'];
			$vf = $c['v'] / $scoreArray['maxV'];
			$cf = $c['count'] / $scoreArray['maxCount'];
			$distPrimary = $c['clusterObj']->getDistanceWith($primary['clusterObj']);
			
			$c['s_score'] = (4*$sf + 2*$vf) * (3*$cf + $distPrimary * 6);
			$c['s_score'] -= $c['p_score']/3;
			
			if($c['s'] < $scoreArray['maxS'] * 0.3) $c['s_score'] *= 0.65;
			if($c['v'] < $scoreArray['maxV'] * 0.1) $c['s_score'] *= 0.55;
			
			if($c['s_score'] > $maxSScore){
				$maxSScore = $c['s_score'];
				$secondaryIdx = $idx;
			}
		});
		$scoreArray['secondary'] = ['maxScore'=>$maxSScore, 'idx'=>$secondaryIdx];
		return $scoreArray['clusters'][$secondaryIdx];
	}
	
	// util class stuff
	public function __construct(){
		throw new \Exception(get_class() . " is an utility class and should be used statically");
	}
}
