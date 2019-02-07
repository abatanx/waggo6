<?php
/**
 * waggo6
 * @copyright 2013-2019 CIEL, K.K.
 * @license MIT
 */

global $WG_THUMBNAIL_SIZES, $WG_THUMBNAIL_FORMATS, $WG_MOVIE_FORMATS;

$WG_THUMBNAIL_SIZES =
	[
		[ 'maxpx'=>640 , 'previd' => ["640"] ] ,
		[ 'maxpx'=>140 , 'previd' => ["140","preview","prev","p"] ],
		[ 'maxpx'=>75  , 'previd' => ["75","face","f","mini","m"] ]
	];

$WG_THUMBNAIL_FORMATS =
	[
		''    => 'x' ,
		'jpg' => 'j' ,
		'png' => 'p'
	];

$WG_MOVIE_FORMATS =
	[
		"flv" => [ 'filename' => 'pc.flv' ,			'ffm_params' => '-s 512x384 -ar 22050' ],
		"3gp" => [ 'filename' => 'mob.3gp',			'ffm_params' => '-s 176x144 -ar 8000 -ab 7.4k -ac 1' ],
		"jpg" => [ 'filename' => 'image.jpg',		'ffm_params' => '-s 512x384 -f mjpeg -vframes 1 -an' ]
	];

