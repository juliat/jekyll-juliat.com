<?php

// Get settings
require_once('config.php');

// Cache file
$cache_file = 'cache/flickr.txt';

// Time that the cache was last filled.
$cache_file_created = ((@file_exists($cache_file))) ? @filemtime($cache_file) : 0;

$images_found = false;

// Show file from cache if still valid.
if (time() - $flickr_cachetime < $cache_file_created) {

	$images_found = true;

	// Display images from the cache.
	@readfile($cache_file);	

} else {

	// Fetch the RSS feed from Flickr.
	$url = "http://api.flickr.com/services/feeds/photos_public.gne?id=$your_flickr_id&format=rss";

	// Initiate the curl session
	$ch = curl_init();

	// Set the URL
	curl_setopt($ch, CURLOPT_URL, $url);

	// Removes the headers from the output
	curl_setopt($ch, CURLOPT_HEADER, 0);

	// Return the output instead of displaying it directly
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	// Execute the curl session
	$rss_feed = curl_exec($ch);

	// Close the curl session
	curl_close($ch);

	// Parse the RSS feed to an XML object.
	$rss_feed = @simplexml_load_string($rss_feed);

	if(isset($rss_feed)) {

		$image = $rss_feed->channel->item;

		// Error check: Make sure there is at least one item.
		if(count($image)) {

			// Start output buffering.
			ob_start();

			// Open the flickr wrapping element.
			$output = "";

			$images_found = true;

			for($i=0; $i < $flickr_limit; $i++) {

				// Get thumbnail size
				preg_match('/<img[^>]*>/i', $image[$i]->description, $image_tag);
				preg_match('/(?<=src=[\'|"])[^\'|"]*?(?=[\'|"])/i', $image_tag[0], $image_src);
				
				if (preg_match('/(_m.jpg)$/',$image_src[0])){
					$thumb = preg_replace('/(_m.jpg)$/', '_s.jpg', $image_src[0]);
				} elseif(preg_match('/(_m.png)$/',$image_src[0])){
					$thumb = preg_replace('/(_m.png)$/', '_s.png', $image_src[0]);
				} elseif(preg_match('/(_m.gif)$/',$image_src[0])){
					$thumb = preg_replace('/(_m.gif)$/', '_s.gif', $image_src[0]);
				}

				$image_link = $image[$i]->link;
				$image_title = $image[$i]->title;

				$output .= "<li><a href='$image_link' title='$image_title'><img src='$thumb' alt='$image_title'></a></li>\n";

			}

			// Close the flickr wrapping element.
			echo $output;

			// Generate a new cache file.
			$file = @fopen($cache_file, 'w');

			// Save the contents of output buffer to the file, and flush the buffer. 
			@fwrite($file, ob_get_contents()); 
			@fclose($file); 
			ob_end_flush();

		}
	
	}

}

// In case the RSS feed did not parse or load correctly, show a link to the Flickr account.
if (!$images_found){
	echo $output = "<li>Oops, our Flickr feed is unavailable at the moment - <a href='http://flickr.com/$your_flickr_id/'>Check our images on Flickr!</a></li>";
}