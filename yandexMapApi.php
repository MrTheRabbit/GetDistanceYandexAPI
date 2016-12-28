<?php

namespace App;

/**
 * YandexMapApi
 * Библиотека возвращающая расстояние от МКАД/КАД до населенного пункта. 
 * Применяется для расчета доставки.
 * 
 * @package 
 * @author TheRabbit
 * @copyright 2016
 * @version $Id$
 * @access public
 */
class YandexMapApi
{
	/**
	 * YandexMapApi::SearchObjectByAddress()
	 * 
	 * @param mixed $strAddress
	 * @return
	 */
	public function SearchObjectByAddress($strAddress) {

		$objCurl = curl_init();
		curl_setopt($objCurl, CURLOPT_URL, 'http://geocode-maps.yandex.ru/1.x/?geocode='.urlencode($strAddress).'&results=1');
		curl_setopt($objCurl, CURLOPT_HEADER, 0);
		curl_setopt($objCurl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($objCurl, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
		curl_setopt($objCurl, CURLOPT_SSL_VERIFYPEER, false);
		$strResult = curl_exec($objCurl);
		curl_close($objCurl);
		
		return $strResult;
	}//\\ SearchObjectByAddress

	/**
	 * YandexMapApi::SearchObject()
	 * 
	 * @param mixed $strLatitude
	 * @param mixed $strLongitude
	 * @return
	 */
	public function SearchObject($strLatitude, $strLongitude) {

		$objCurl = curl_init();
		curl_setopt($objCurl, CURLOPT_URL, 'http://geocode-maps.yandex.ru/1.x/?geocode='.$strLatitude.','.$strLongitude.'&results=1');
		curl_setopt($objCurl, CURLOPT_HEADER, 0);
		curl_setopt($objCurl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($objCurl, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
		curl_setopt($objCurl, CURLOPT_SSL_VERIFYPEER, false);
		$strResult = curl_exec($objCurl);
		curl_close($objCurl);

		return $strResult;
	}//\\ SearchObject

	/**
	 * YandexMapApi::GetUrlMapImage()
	 * 
	 * @param mixed $ResultSearchObject
	 * @param mixed $intZPosition
	 * @param mixed $intWidth
	 * @param mixed $intHeight
	 * @return
	 */
	public function GetUrlMapImage($ResultSearchObject, $intZPosition, $intWidth, $intHeight) {
		$strPoint = $this->GetPoint($ResultSearchObject);
		return 'http://static-maps.yandex.ru/1.x/?ll=$strPoint&size='.$intWidth.','.$intHeight.'&z='.$intZPosition.'&l=map&pt='.$strPoint.',pm2lbm&lang=ru-RU';
	}//\\ GetUrlMapImage

	/**
	 * YandexMapApi::GetPoint()
	 * 
	 * @param mixed $ResultSearchObject
	 * @return
	 */
	public function GetPoint($ResultSearchObject) {
		
		$strPoint = '';
		$objXml = simplexml_load_string($ResultSearchObject);

		$strPoint = $objXml->GeoObjectCollection->featureMember->GeoObject->Point->pos;
		$strPoint = str_replace(' ', ',', $strPoint);

		return $strPoint;
	}//\\ GetPoint

	/**
	 * YandexMapApi::GetPointObject()
	 * 
	 * @param mixed $ResultSearchObject
	 * @return
	 */
	public function GetPointObject($ResultSearchObject) {
		
		$objResult = new YandexMapApiPoint($this->GetPoint($ResultSearchObject));
		return $objResult;
	}//\\ GetPointObject
	
	/**
	 * YandexMapApi::GetDistance()
	 * 
	 * @param mixed $objA
	 * @param mixed $objB
	 * @return
	 */
	public function GetDistance($objA, $objB) {
		
		$intR = 6371;
		$dLat = $this->toRad($objB->Lat() - $objA->Lat());
		$dLon = $this->toRad($objB->Long() - $objA->Long());
		$lat1 = $this->toRad($objA->Lat());
		$lat2 = $this->toRad($objB->Lat());

		$intA = sin($dLat / 2) * sin($dLat / 2) + sin($dLon / 2) * sin($dLon / 2) * cos($lat1) * cos($lat2); 
		$intC = 2 * atan2(sqrt($intA), sqrt(1 - $intA)); 
		$intD = $intR * $intC;
		
		return $intD;
	}//\\ GetDistance
	
	/**
	 * YandexMapApi::CheckMkad()
	 * 
	 * @param mixed $strAddress
	 * @return
	 */
	public function CheckMkad($strAddress) {
		
		$objPolyhon = YandexMapApiPolygon::GetMkadPolygon();
		$strXml = $this->SearchObjectByAddress($strAddress);
		$coordinates = $this->GetPoint($strXml);
		$objPoint = new YandexMapApiPoint($coordinates);
		$booIsKad = $objPolyhon->IsInPolygon($objPoint);
		$objClosePoint = $objPolyhon->GetClosestPoint($objPoint);
		$intDistance = $this->GetDistance($objClosePoint, $objPoint);
		
		$arrResult = [
			'point' => $objPoint,
			'closest_point' => $objClosePoint,
			'is_in_polygon' => $booIsKad,
			'distance' => $intDistance
		];
		
		return $arrResult;
	}//\\ CheckMkad
	
	/**
	 * YandexMapApi::CheckKad()
	 * 
	 * @param mixed $strAddress
	 * @return
	 */
	public function CheckKad($strAddress) {
		
		$objPolyhon = YandexMapApiPolygon::GetKadPolygon();
		$strXml = $this->SearchObjectByAddress($strAddress);
		$coordinates = $this->GetPoint($strXml);
		$objPoint = new YandexMapApiPoint($coordinates);
		$booIsMkad = $objPolyhon->IsInPolygon($objPoint);
		$objClosePoint = $objPolyhon->GetClosestPoint($objPoint);
		$intDistance = $this->GetDistance($objClosePoint, $objPoint);
		
		$arrResult = [
			'point' => $objPoint,
			'closest_point' => $objClosePoint,
			'is_in_polygon' => $booIsMkad,
			'distance' => $intDistance
		];
		
		return $arrResult;
	}//\\ CheckKad
	
	/**
	 * YandexMapApi::toRad()
	 * 
	 * @param mixed $intV
	 * @return
	 */
	protected function toRad($intV) {
		return $intV * pi() / 180;
	}//\\ toRad
	
}//\\ YandexMapApi

/**
 * YandexMapApiPolygon
 * Класс по работе с полигонами.
 * 
 * @package 
 * @author TheRabbit
 * @copyright 2016
 * @version $Id$
 * @access public
 */
class YandexMapApiPolygon {
	
	public $arrPoints;

	/**
	 * YandexMapApiPolygon::__construct()
	 * 
	 * @param mixed $arrPoints
	 * @return void
	 */
	public function __construct($arrPoints = null) {
		$this->arrPoints = $arrPoints;
		
		if (empty($this->arrPoints)) {
			$this->arrPoints = [];
		}//\\ if
	}//\\ __construct

	/**
	 * YandexMapApiPolygon::IsInPolygon()
	 * 
	 * @param mixed $objMainPoint
	 * @return
	 */
	public function IsInPolygon($objMainPoint) {
		
		$booResult = false;

		for ($i = 0, $j = count($this->arrPoints) - 1; $i < count($this->arrPoints); $j = $i++) {
			if ($this->arrPoints[$i]->y < $objMainPoint->y && $this->arrPoints[$j]->y >= $objMainPoint->y || $this->arrPoints[$j]->y < $objMainPoint->y && $this->arrPoints[$i]->y >= $objMainPoint->y) {
				if ($this->arrPoints[$i]->x + ($objMainPoint->y - $this->arrPoints[$i]->y ) / ($this->arrPoints[$j]->y - $this->arrPoints[$i]->y ) * ($this->arrPoints[$j]->x - $this->arrPoints[$i]->x ) < $objMainPoint->x) {
					$booResult = !$booResult;
				}//\\ if
			}//\\ if
		}//\\ for
		
		return $booResult ? true : false;
	}//\\ IsInPolygon
	
	/**
	 * YandexMapApiPolygon::GetClosestPoint()
	 * 
	 * @param mixed $objPoint
	 * @return
	 */
	public function GetClosestPoint($objPoint) {
		
		$objApi = new YandexMapApi();
		$arrResult = [];
		$arrSort = [];
		
		foreach ($this->points as $objP) {
			$intD = $objApi->GetDistance($objP, $objPoint);
			$arrResult[$intD] = $objP;
			$arrSort[] = $objP;
		}//\\ foreach
		
		$intKey = min($arrSort);
		
		return $arrResult[$intKey];
	}//\\ GetClosestPoint

	/**
	 * YandexMapApiPolygon::GetPointsFromString()
	 * 
	 * @param mixed $strPoints
	 * @return
	 */
	public static function GetPointsFromString($strPoints) {
		
		$arrCoordinates = explode(' ', $strPoints);
		$arrResult = array_fill(0, count($arrCoordinates) / 2, null);

		$intJ = 0;
		
		for ($intI = 0; $intI < count(coordinates);  $intI = $intI + 2) {
			$objPoint = new YandexMapApiPoint();
			$objPoint->x = (double)$arrCoordinates[$intI];
			$objPoint->y = (double)$arrCoordinates[$intI + 1];

			$arrResult[$intJ] = $objPoint;

			if ($intJ >= count($arrResult)) {
				break;
			}//\\ if

			$intJ++;
		}//\\ for

		return $arrResult;
	}//\\ GetPointsFromString

	/**
	 * YandexMapApiPolygon::GetKadPolygon()
	 * 
	 * @return
	 */
	public static function GetKadPolygon() {
		
		$arrData = [
			[29.667552,59.927218],
			[29.680598,59.96201],
			[29.692958,59.989542],
			[29.701198,60.001237],
			[29.719051,60.014989],
			[29.738277,60.021176],
			[29.773296,60.021176],
			[29.787715,60.022207],
			[29.830974,60.025987],
			[29.894145,60.030798],
			[29.953883,60.036638],
			[29.978603,60.039043],
			[29.997829,60.039386],
			[30.018428,60.041447],
			[30.061687,60.047972],
			[30.096706,60.052436],
			[30.133098,60.057243],
			[30.154384,60.061705],
			[30.16537,60.065481],
			[30.181163,60.076119],
			[30.199702,60.082637],
			[30.227855,60.086068],
			[30.249828,60.093956],
			[30.250514,60.100471],
			[30.296087,60.099442],
			[30.31806,60.096013],
			[30.352392,60.094299],
			[30.368185,60.092241],
			[30.377111,60.085725],
			[30.383291,60.070629],
			[30.386724,60.061705],
			[30.394277,60.05587],
			[30.42243,60.048316],
			[30.440283,60.041104],
			[30.443716,60.031141],
			[30.460882,60.018427],
			[30.475302,60.008458],
			[30.484915,59.989886],
			[30.494528,59.984726],
			[30.515814,59.981285],
			[30.539846,59.975435],
			[30.552206,59.966486],
			[30.552893,59.956501],
			[30.540533,59.944446],
			[30.5371,59.931353],
			[30.525427,59.920324],
			[30.52474,59.903428],
			[30.525427,59.892044],
			[30.526113,59.884107],
			[30.531607,59.874443],
			[30.529547,59.867538],
			[30.517874,59.860631],
			[30.508261,59.853724],
			[30.473242,59.850269],
			[30.460195,59.84716],
			[30.448522,59.834028],
			[30.434103,59.824695],
			[30.411444,59.819855],
			[30.383978,59.815705],
			[30.364752,59.81363],
			[30.352392,59.81363],
			[30.327673,59.808096],
			[30.300207,59.824349],
			[30.283041,59.833337],
			[30.265875,59.833683],
			[30.246649,59.829881],
			[30.230856,59.825387],
			[30.217123,59.819509],
			[30.19927,59.813284],
			[30.182104,59.808442],
			[30.171118,59.801177],
			[30.153951,59.797717],
			[30.115499,59.813284],
			[30.071554,59.817088],
			[30.022116,59.816742],
			[30.001516,59.818126],
			[29.978857,59.821929],
			[29.960317,59.820892],
			[29.912252,59.811555],
			[29.874487,59.812247],
			[29.849768,59.814667],
			[29.831228,59.821929],
			[29.812689,59.839904],
			[29.805136,59.858214],
			[29.785909,59.865466],
			[29.758444,59.868919],
			[29.732351,59.873407],
			[29.676733,59.886868],
			[29.662313,59.897564],
			[29.657507,59.914463]
		];
		
		$arrPoints = [];
		
		foreach ($arrData as $arrD) {
			$arrPoints[] = new YandexMapApiPoint(null, $arrD[0], $arrD[1]);
		}//\\ foreach
		
		$objPolygon = new YandexMapApiPolygon($arrPoints);
		
		return $objPolygon;
	}//\\ GetKadPolygon
	
	/**
	 * YandexMapApiPolygon::GetMkadPolygon()
	 * 
	 * @return
	 */
	public static function GetMkadPolygon() {
		
		$arrData = [
			[37.842762, 55.774558],
			[37.842789, 55.76522],
			[37.842627, 55.755723],
			[37.841828, 55.747399],
			[37.841217, 55.739103],
			[37.840175, 55.730482],
			[37.83916, 55.721939],
			[37.837121, 55.712203],
			[37.83262, 55.703048],
			[37.829512, 55.694287],
			[37.831353, 55.68529],
			[37.834605, 55.675945],
			[37.837597, 55.667752],
			[37.839348, 55.658667],
			[37.833842, 55.650053],
			[37.824787, 55.643713],
			[37.814564, 55.637347],
			[37.802473, 55.62913],
			[37.794235, 55.623758],
			[37.781928, 55.617713],
			[37.771139, 55.611755],
			[37.758725, 55.604956],
			[37.747945, 55.599677],
			[37.734785, 55.594143],
			[37.723062, 55.589234],
			[37.709425, 55.583983],
			[37.696256, 55.578834],
			[37.683167, 55.574019],
			[37.668911, 55.571999],
			[37.647765, 55.573093],
			[37.633419, 55.573928],
			[37.616719, 55.574732],
			[37.60107, 55.575816],
			[37.586536, 55.5778],
			[37.571938, 55.581271],
			[37.555732, 55.585143],
			[37.545132, 55.587509],
			[37.526366, 55.5922],
			[37.516108, 55.594728],
			[37.502274, 55.60249],
			[37.49391, 55.609685],
			[37.484846, 55.617424],
			[37.474668, 55.625801],
			[37.469925, 55.630207],
			[37.456864, 55.641041],
			[37.448195, 55.648794],
			[37.441125, 55.654675],
			[37.434424, 55.660424],
			[37.42598, 55.670701],
			[37.418712, 55.67994],
			[37.414868, 55.686873],
			[37.407528, 55.695697],
			[37.397952, 55.702805],
			[37.388969, 55.709657],
			[37.383283, 55.718273],
			[37.378369, 55.728581],
			[37.374991, 55.735201],
			[37.370248, 55.744789],
			[37.369188, 55.75435],
			[37.369053, 55.762936],
			[37.369619, 55.771444],
			[37.369853, 55.779722],
			[37.372943, 55.789542],
			[37.379824, 55.79723],
			[37.386876, 55.805796],
			[37.390397, 55.814629],
			[37.393236, 55.823606],
			[37.395275, 55.83251],
			[37.394709, 55.840376],
			[37.393056, 55.850141],
			[37.397314, 55.858801],
			[37.405588, 55.867051],
			[37.416601, 55.872703],
			[37.429429, 55.877041],
			[37.443596, 55.881091],
			[37.459065, 55.882828],
			[37.473096, 55.884625],
			[37.48861, 55.888897],
			[37.5016, 55.894232],
			[37.513206, 55.899578],
			[37.527597, 55.90526],
			[37.543443, 55.907687],
			[37.559577, 55.909388],
			[37.575531, 55.910907],
			[37.590344, 55.909257],
			[37.604637, 55.905472],
			[37.619603, 55.901637],
			[37.635961, 55.898533],
			[37.647648, 55.896973],
			[37.667878, 55.895449],
			[37.681721, 55.894868],
			[37.698807, 55.893884],
			[37.712363, 55.889094],
			[37.723636, 55.883555],
			[37.735791, 55.877501],
			[37.741261, 55.874698],
			[37.764519, 55.862464],
			[37.765992, 55.861979],
			[37.788216, 55.850257],
			[37.788522, 55.850383],
			[37.800586, 55.844167],
			[37.822819, 55.832707],
			[37.829754, 55.828789],
			[37.837148, 55.821072],
			[37.838926, 55.811599],
			[37.840004, 55.802781],
			[37.840965, 55.793991],
			[37.841576, 55.785017]
		);
		
		$arrPoints = [];
		
		foreach ($arrData as $arrD) {
			$arrPoints[] = new YandexMapApiPoint(null, $arrD[0], $arrD[1]);
		}//\\ foreach
		
		$objPolygon = new YandexMapApiPolygon($arrPoints);
		
		return $objPolygon;
	}//\\ GetMkadPolygon

}//\\ YandexMapApiPolygon

/**
 * YandexMapApiPoint
 * Класс по работе с координатами.
 * 
 * @package 
 * @author TheRabbit
 * @copyright 2016
 * @version $Id$
 * @access public
 */
class YandexMapApiPoint {
	
	public $x;
	public $y;

	/**
	 * YandexMapApiPoint::__construct()
	 * 
	 * @param mixed $strPoint
	 * @param mixed $intX
	 * @param mixed $intY
	 * @return void
	 */
	public function __construct($strPoint = null, $intX = null, $intY = null) {
		
		if (!empty($strPoint)) {
			$arrCoordinate = explode(',', $strPoint);
			$this->x = (double)$arrCoordinate[0];
			$this->y = (double)$arrCoordinate[1];
		}//\\ if
		
		if (!empty($intX)) {
			$this->x = $intX;
		}//\\ if
		
		if (!empty($intY)) {
			$this->y = $intY;
		}//\\ if
	}//\\ __construct

	/**
	 * YandexMapApiPoint::ConvertToGPSPoint()
	 * 
	 * @param mixed $point
	 * @return
	 */
	public function ConvertToGPSPoint($point) {
		
		$objResult = new YandexMapApiPoint();

		$intX = (int)$point->x;
		$objResult->x = $intX + ($point->x - $intX) * 0.6;

		$intY = (int)$point->y;
		$objResult->y = $intY + ($point->y - $intY) * 0.6;

		return $objResult;
	}//\\ ConvertToGPSPoint

	/**
	 * YandexMapApiPoint::ConvertGPSToYandexPoint()
	 * 
	 * @param mixed $point
	 * @return
	 */
	public function ConvertGPSToYandexPoint($point) {
		
		$objResult = new YandexMapApiPoint();

		$intX = (int)$point->x;
		$objResult->x = $intX + ($point->x - $intX) / 0.6;

		$intY = (int)$point->y;
		$objResult->y = $intY + ($point->y - $intY) / 0.6;

		return $objResult;
	}//\\ ConvertGPSToYandexPoint

	/**
	 * YandexMapApiPoint::ToString()
	 * 
	 * @return
	 */
	public function ToString() {
		return $this->x.', '.$this->y;
	}//\\ ToString
	
	/**
	 * YandexMapApiPoint::Latitude()
	 * 
	 * @return
	 */
	public function Latitude() {
		return $this->y;
	}//\\ Latitude
	
	/**
	 * YandexMapApiPoint::Lat()
	 * 
	 * @return
	 */
	public function Lat() {
		return $this->Latitude();
	}//\\ Lat
	
	/**
	 * YandexMapApiPoint::Longitude()
	 * 
	 * @return
	 */
	public function Longitude() {
		return $this->x;
	}//\\ Longitude
	
	/**
	 * YandexMapApiPoint::Long()
	 * 
	 * @return
	 */
	public function Long() {
		return $this->Longitude();
	}//\\ Long
}//\\ YandexMapApiPoint


