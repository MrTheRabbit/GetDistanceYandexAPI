<?php

use App\YandexMapApi;
use App\YandexMapApiPoint;
use App\YandexMapApiPolygon;


$objYapi = new YandexMapApi();
$arrResult = $objYapi->CheckMkad('Химки');
var_dump($arrResult); //Выводим результат

$arrResult = $objYapi->CheckKad('Гатчина');
var_dump($arrResult); //Выводим результат
