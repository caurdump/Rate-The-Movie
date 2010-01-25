<?php
/*
 	This is a crawler that crawls movies.google.com for a given location
 	
	In case of any curl or dom errors with this code, in php.ini make sure that
	1. extension=php_curl.dll should be uncommented
	2. extension=php_domxml.dll should be commented
	
	Reference:
	1.http://www.developertutorials.com/tutorials/php/scraping-links-with-php-8-01-05/page1.html
	2.http://www.phpro.org/tutorials/Introduction-to-PHP-Regex.html	
	3.http://www.webcheatsheet.com/php/regular_expressions.php
	
	Todo:
	.Change $url in format_url
	.Try to change data format from xml to json
	
*/



function get_thumbnail_url($apiKey,$movieName) /*Return the url for thumbnail image given the movie name and MovieDB API Key.
												Return "none" if no movie found */
{
	//Parsing XML using SimpleXML
	$url = 'http://api.themoviedb.org/2.1/Movie.search/en/xml/'.$apiKey.'/'.$movieName; 
	$xml = simplexml_load_file($url); 
			
	if($xml->movies == 'Nothing found.') { /* No match found, so return "none" */
		return "none";
	}
		
	foreach ($xml->movies->movie[0]->images->image  as $image) { /* Iterate through the image urls of the first movie to get thumbnail url */
		if($image['type'] == 'poster' && $image['size'] == 'thumb') {
			return format_url($image['url'],$movieName);
			
		}	
	}
	
	return "none" ; //Its possible that movie exists but no image for it does
}

function format_url($url,$movieName)/*To return proper url for urls like http://images.themoviedb.org/posters/87579/poster_thumb.,
						 caused by a bug in MovieDB API*/
{	
	
	if(preg_match("/poster_thumb.\z/i", $url)){ /* URL ends in poster_thumb. */
		//File is cached on the server,with the $movieName with all spaces removed as its name
		$fileName = str_replace(" ", "", $movieName).".jpg";
		//Check if we already have the image file
		$filePath = "images/".$fileName;	
		if(! file_exists($filePath)) { /* If we dont,then download and save it */
			file_put_contents($filePath,file_get_contents($url));
		}
		
		$url = "http://localhost/ratethemovie/".$filePath;//"http://www.razasayed.com/ratethemovie/".$filePath; 
			
	}
	
	return $url ;
	
}

function get_current_date()
{
	$timestamp = time();
	return date("F jS, Y", $timestamp);
}

function generate_xml_file($totalResults,$movieList)
{
	//$file= fopen("movie_results.xml", "w");
	$xml_writer = new XMLWriter();
	$xml_writer->openMemory();
	$xml_writer->startDocument('1.0', 'UTF-8', 'yes');
	$xml_writer->startElement('movies');
	$xml_writer->writeAttribute('date',get_current_date());	// Like December 23rd, 2009
	$xml_writer->writeAttribute('total',$totalResults);	
	foreach($movieList as $key => $value) {
		$xml_writer->startElement('movie');
		$xml_writer->writeElement('name',$key);
		$xml_writer->writeElement('url',$value);
		$xml_writer->endElement(); //movie
	}
	$xml_writer->endElement(); //movies
	$xml_data = $xml_writer->outputMemory();
	print $xml_data;
	//fwrite($file,$xml_data);
	//fclose($file);
}

function fetch_movie_data()
{
	$targetUrl="http://www.google.com/movies?near=".$_POST['location']."&sort=1";
	$userAgent = 'Googlebot/2.1 (http://www.googlebot.com/bot.html)';  /* Fake the UA :P */
	$apiKey = 'f580be8a2b8ae82c60a6e328942bb214' ; /* MovieDB API Key */
	$movieList = array(); /* Holds the movie name and url for every movie as a key value pair */
	$totalResults = 0 ; /* Total number of movies being returned */
	
	/* Curl boiler plate code */	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
	curl_setopt($ch, CURLOPT_URL,$targetUrl);
	curl_setopt($ch, CURLOPT_FAILONERROR, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_AUTOREFERER, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);

	$dom = new DOMDocument();

	do {
		
		$foundNext = 0;
		
		//Fetch the page html
		$html = curl_exec($ch); 
		if (!$html) { /* On error print error info and exit */
			echo "curl error number:".curl_errno($ch);
			echo "curl error:".curl_error($ch);
			exit;
		}	
		
		
		//Get names and image thumbnail urls for each movie on this page
		@$dom->loadHTML($html);
		$xpath = new DOMXPath($dom);
		$htwos = $xpath->evaluate("/html/body//h2"); /* Get all h2 elements, as all movie names and only movie names are marked up as h2 */
		if($htwos->length == 0){ /* No movies found */
			print "None";
			exit;
		}		
		for ($i = 0; $i < $htwos->length; $i++) { /* Store the movie names and their thumbnail urls as key value pairs in array movieList */
			$htwo = $htwos->item($i);
			$thumbnailUrl = get_thumbnail_url($apiKey,$htwo->nodeValue);
			if($thumbnailUrl != "none") { /*If thumbnail for this movie found */
				$movieList[$htwo->nodeValue] = $thumbnailUrl;
				$totalResults++;
			}
		}
		
		//Check if there is a next
		$hrefs = $xpath->evaluate("/html/body//a");
		for ($i = 0; $i < $hrefs->length; $i++) {
			$href = $hrefs->item($i);
			if($href->nodeValue == "Next"){
				$foundNext = 1;
				$url = "http://www.google.com".$href->getAttribute('href');
				curl_setopt($ch,CURLOPT_URL,$url);
				break;
				
			}
		}
		
		
		
	}while($foundNext);	

    generate_xml_file($totalResults,$movieList);
}

fetch_movie_data();

?>