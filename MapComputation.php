<?PHP
/***    地圖計算
  *
**/
namespace Think\Selflib;
class MapComputaion
{	
	function __construct()
	{
        $this->ra = 6378137;       # 赤道半徑
        $this->rb = 6356752.3142;  # 極半徑
	}  
	/**  白塞尔大地主题正算
	*	$a	長軸半徑(m)
	*	$b	短軸半徑(m)
	* 	$c	極曲率半徑
	* 	$alpha	扁率
	* 	$e	第一偏心率
	* 	$e2	第二偏心率
	* 	$W	第一基本緯度函數
	* 	$V	第二基本緯度函數

	* 	$B1	點1的緯度
	* 	$L1	點1的經度
	* 	$B2	點2的緯度
	* 	$L2	點2的經度

	* 	$S	兩點直線距離(m)

	* 	$A1	點1到點2的方位角(degree)
	* 	$A2	點2到點1的方位角(degree)
	*/
	function getCoordinate($STARTLAT, $STARTLONG, $ANGLES, $DISTANCE) 
	{
		$B1 = $STARTLAT;
		$L1 = $STARTLONG;
		$A1 = $ANGLES;
		$S = $DISTANCE;
		
		$a = $this->ra;
		$b = $this->rb;
		// $c = pow($a, 2) / $b;
		// $alpha = ($a - $b) / $a;
		$e = sqrt( pow($a, 2) - pow($b, 2) ) / $a;
		$e2 = sqrt( pow($a, 2) - pow($b, 2) ) / $b;
		
		
			
		$B1 = $B1 * M_PI / 180;
		$L1 = $L1 * M_PI / 180;
		$A1 = $A1 * M_PI / 180;
		
		$W = sqrt( 1 - pow($e, 2) * pow(sin($B1), 2));
		// $V = $W * ($a / $b);
		
		$W1 = 0;
		$E1 = $e; // 第一偏心率
		
		// 計算起點的歸化緯度
		$W1 = $W;
		$sinu1 = sin($B1) * sqrt(1 - $E1 * $E1) / $W1;
		$cosu1 = cos($B1) / $W1;
		// 計算輔助函數值
		$sinA0 = $cosu1 * sin($A1);
		$cotq1 = $cosu1 * cos($A1);
		$sin2q1 = 2 * $cotq1 / (pow($cotq1, 2) + 1);
		$cos2q1 = ( pow($cotq1, 2) - 1 ) / ( pow($cotq1, 2) + 1 );


		// 計算系數AA,BB,CC及AAlpha, BBeta的值。
		$cos2A0 = 1 - pow($sinA0, 2);
		$k2	 = $e2 * $e2 * $cos2A0;


		//Dim aa, BB, CC, EE22, AAlpha, BBeta As doubleval()
		$aa = $b * (1 + $k2 / 4 - 3 * $k2 * $k2 / 64 + 5 * $k2 * $k2 * $k2 / 256);
		$BB = $b * ($k2 / 8 - $k2 * $k2 / 32 + 15 * $k2 * $k2 * $k2 / 1024);
		$CC = $b * ($k2 * $k2 / 128 - 3 * $k2 * $k2 * $k2 / 512);
		$ee = $E1 * $E1;
		$AAlpha = ($ee / 2 + $ee * $ee / 8 + $ee * $ee * $ee / 16) - ($ee * $ee / 16 + $ee * $ee * $ee / 16) * $cos2A0 + (3 * $ee * $ee * $ee / 128) * $cos2A0 * $cos2A0;
		$BBeta = ($ee * $ee / 32 + $ee * $ee * $ee / 32) * $cos2A0 - ($ee * $ee * $ee / 64) * $cos2A0 * $cos2A0;


		// 計算球面長度
		$q0 = ($S - ($BB + $CC * $cos2q1) * $sin2q1) / $aa;
		$sin2q1q0 = $sin2q1 * cos(2 * $q0) + $cos2q1 * sin(2 * $q0);
		$cos2q1q0 = $cos2q1 * cos(2 * $q0) - $sin2q1 * sin(2 * $q0);
		$q = $q0 + ($BB + 5 * $CC * $cos2q1q0) * $sin2q1q0 / $aa;
		// 計算經度差改正數
		
		$theta = ($AAlpha * $q + $BBeta * ($sin2q1q0 - $sin2q1)) * $sinA0;


		// 計算終點大地坐標及大地方位角
		$sinu2 = $sinu1 * cos($q) + $cosu1 * cos($A1) * sin($q);
		$B2 = atan($sinu2 / (sqrt(1 - $E1 * $E1) * sqrt(1 - $sinu2 * $sinu2))) * 180 / M_PI;
		$lamuda = atan(sin($A1) * sin($q) / ($cosu1 * cos($q) - $sinu1 * sin($q) * cos($A1))) * 180 / M_PI;
					 
		if(sin($A1) > 0) {
			if(sin($A1) * sin($q) / ($cosu1 * cos($q) - $sinu1 * sin($q) * cos($A1)) > 0)
				$lamuda = abs($lamuda);
			else
				$lamuda = 180 - abs($lamuda);
		}
		else {
			if(sin($A1) * sin($q) / ($cosu1 * cos($q) - $sinu1 * sin($q) * cos($A1)) > 0) {
				$lamuda = abs($lamuda) - 180;
			}
			else
				$lamuda = -abs($lamuda);
		}
		$L2 = $L1 * 180 / M_PI + $lamuda - $theta * 180 / M_PI;

		return array('lat'=>$B2, 'lng'=>$L2);
	}

	/**
	 *  兩經緯度求距離
	 *  @param  string $latA, $lonA, $latB, $lonB 經緯度(角度)
	 *  @return string $distance 大地距離(m)
	 */
	function getDistance($latA, $lonA, $latB, $lonB){
		if($latA==$latB && $lonA==$lonB)
			return 0;
	    $ra = $this->ra;
	    $rb = $this->rb;
	    $flatten = ($ra - $rb) / $ra;  # Partial $rate of the earth
	    # change angle to $radians
	    $radLatA = $this->radians($latA);
	    $radLonA = $this->radians($lonA);
	    $radLatB = $this->radians($latB);
	    $radLonB = $this->radians($lonB);

	    $pA = atan($rb / $ra * tan($radLatA));
	    $pB = atan($rb / $ra * tan($radLatB));
	    $x = acos(sin($pA) * sin($pB) + cos($pA) * cos($pB) * cos($radLonA - $radLonB));
	    $c1 = (sin($x) - $x) * (sin($pA) + sin($pB)) ** 2 / cos($x / 2) ** 2;
	    $c2 = (sin($x) + $x) * (sin($pA) - sin($pB)) ** 2 / sin($x / 2) ** 2;
	    $dr = $flatten / 8 * ($c1 - $c2);
	    $distance = $ra * ($x + $dr);
	    return $distance;
	}
	/**
	 *  兩經緯度求角度
	 *  @param  string $latA, $lonA, $latB, $lonB 經緯度(角度)
	 *  @return string $brng 方向角(角度)
	 */
	function getDegree($latA, $lonA, $latB, $lonB){
	    $radLatA = $this->radians($latA);
	    $radLonA = $this->radians($lonA);
	    $radLatB = $this->radians($latB);
	    $radLonB = $this->radians($lonB);
	    $dLon = $radLonB - $radLonA;
	    $y = sin($dLon) * cos($radLatB);
	    $x = cos($radLatA) * sin($radLatB) - sin($radLatA) * cos($radLatB) * cos($dLon);
	    $brng = $this->degrees(atan2($y, $x));
	    $brng = round(($brng + 360) % 360, 4);
	    $brng = intval($brng);
	    if (($brng == 0.0) or (($brng == 360.0)))
	        return '正北方向';
	    else if ($brng == 90.0)
	        return '正東方向';
	    else if ($brng == 180.0)
	        return '正南方向';
	    else if ($brng == 270.0)
	        return '正西方向';
	    else if (0 < $brng && $brng < 90)
	        return "北偏東{$brng}";
	    else if (90 < $brng && $brng < 180)
	        return "東偏南".($brng - 90);
	    else if (180 < $brng && $brng < 270)
	        return "西偏南".(270 - $brng);
	    else if (270 < $brng && $brng < 360)
	        return "北偏西".($brng - 270);
	    else
	        return "null";
	}

	/* 角度轉弧度 */
	function radians($angle){
		return $angle * M_PI / 180;
	}
	/* 弧度轉角度 */
	function degrees($radian){
		return $radian * 180 / M_PI;
	}

}

?>
