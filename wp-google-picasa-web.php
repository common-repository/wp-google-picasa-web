<?php 

/*
	Plugin Name: WP Google Picasa Web
	Version: 1.0
	Author: Rajesh Kumar Sharma
	Author URI: http://sitextensions.com
	Description: This plugin is used to show the google picasa web albums. This plugin also provides the shortcode [wp-google-picasa-web].
	Tags: Google Picasa Web, Picasa, ImageViewer, Image listing from google.
*/

add_action( 'admin_menu', 'register_wp_google_picasa_web_menu_page' );
function register_wp_google_picasa_web_menu_page(){
	add_menu_page( 'WP Google Picasa Web', 'Google Picasa Web', 'manage_options', 'wp_google_picasa_web', 'wp_google_picasa_web_callback' ); 
}

add_action( 'admin_init', 'register_wp_google_picasa_web_setting' );
function register_wp_google_picasa_web_setting() {
	register_setting( 'wp_google_picasa_web_options', 'wp_google_picasa_web_options', 'wp_google_picasa_web_options_callback' ); 
} 

function wp_google_picasa_web_options_callback($options = array()){
	return $options;
}

function wp_google_picasa_web_callback(){
	?>
		<div class="wrap">
			<h2>WP Google Picasa Web Options</h2>
			<form method="post" action="options.php">
				<?php 
					settings_fields( 'wp_google_picasa_web_options' );
					$options = get_option('wp_google_picasa_web_options');
					// print_r($options);

					$options['album'] = !empty($options['album']) ? $options['album'] : array();
				?>

				<label>
					Enter Account Key for Google Picasa Web
					<input type="text" name="wp_google_picasa_web_options[account_key]" value="<?php echo $options['account_key']; ?>">
				</label>
				<br>
				<br>

				<?php 
					if(isset($options['account_key']) && !empty($options['account_key'])){

						$albums = load_albums();

						if(!empty($albums)){
							foreach ($albums as $key => $value) {
								# code...

								$parts = parse_url($value['url']);
								parse_str($parts['query'], $query);
								// print_r($query['aID']);

								?>
									<label>
										<input type="checkbox" <?php echo in_array($query['aID'], $options['album']) ? 'checked' : ''; ?> value="<?php echo $query['aID']; ?>" name="wp_google_picasa_web_options[album][]">
										<?php echo $value['title'][0]; ?>
										<p><?php echo $value['description'][0]; ?></p>
										<?php //echo '<pre>';print_r($value); echo '</pre>'; ?>
									</label>
								<?php 
							}
						}

						?>
							
							
						


						<?php 
					}
					submit_button();
				?>

			</form>
		</div>
	<?php 
}



function load_albums(){	

	$options = get_option('wp_google_picasa_web_options');

	$albums = false;
	$account = $options['account_key'];
	
	$thumb_width = $thumb_width.'u';
	if ($thumb_width_crop == 1)
		$thumb_width = $thumb_width.'c';
	
	// $f = Loader::helper('file');					
	$file = getContents("http://picasaweb.google.com/data/feed/api/user/".$account."?kind=album&thumbsize=".$thumb_width);
	
	if (strlen(trim($file)) > 0)
	{			
		$xml = new SimpleXMLElement($file);
		$xml->registerXPathNamespace('gphoto', 'http://schemas.google.com/photos/2007');
		$xml->registerXPathNamespace('media', 'http://search.yahoo.com/mrss/');
		
		foreach($xml->entry as $feed)
		{
			$group = $feed->xpath('./media:group/media:thumbnail');
			$a = $group[0]->attributes();
			$id = $feed->xpath('./gphoto:id');								
			
			// if (@in_array('all', (array)$this->selected_albums) || @in_array($id[0], (array)$this->selected_albums)){
				if (strpos($_SERVER['REQUEST_URI'], '?'))
					$url = $_SERVER['REQUEST_URI'].'&aID='.$id[0];
				else
					$url = $_SERVER['REQUEST_URI'].'?aID='.$id[0];
				
				$albums[] = array(
									'id' => $id[0],
								  'url' => $url,
								  'title' => $feed->title,
								  'description' => $feed->summary,
								  'thumb' => $a[0]
								);					
			// }
		}
	}
	return $albums;
}

function get_albums(){
	$options = get_option('wp_google_picasa_web_options');
	return $options['album'];
}

add_shortcode('wp-google-picasa-web', 'view_albums');
function view_albums(){

	$options = get_option('wp_google_picasa_web_options');

	$data = array();
	$data['account'] = $options['account_key'];
	$data['thumb_width'] = 72;
	$data['thumb_width_crop'] = 1;
	$data['max_width'] = 800;
	$data['alttags'] = 1;
	$data['dimming'] = 1;
	$data['dimmingopacity'] = 75;
	$data['albums'] = get_albums() ? implode(';', get_albums()) : '';

	$controller = (object)$data;

	$account		  = $controller->account;
	$albums		  	  = explode(';', $controller->albums);

	$thumb_width 	  = $controller->thumb_width;
	$thumb_width_crop = $controller->thumb_width_crop;

	if ($thumb_width_crop == 1)
		$thumb_width .= 'c';
	else
		$thumb_width .= 'u';

	$max_width 	 	  = $controller->max_width;
	$max_width 		 .= 'u';

	$dimming 	 	  = $controller->dimming;
	$dimmingopacity   = $controller->dimmingopacity;
	if (strlen($dimmingopacity) == 1)
		$dimmingopacity = '0'.$dimmingopacity;
		
	// $credits 		  = $controller->credits;			
	// $credits_title	  = $controller->credits_title;
	// $credits_text 	  = $controller->credits_text;
	// $credits_url 	  = $controller->credits_url;

	$alttags		  = $controller->alttags;

	// $f = Loader::helper('file');

	if (@!in_array('all', (array)$albums) && sizeof($albums) == 1)
	{
		$_GET['aID'] = $albums[0];
	}



	if ($_GET['aID'] != '' && is_numeric($_GET['aID']))
	{	
		$aID = $_GET['aID'];
		// $file = $f->getContents('http://picasaweb.google.com/data/feed/api/user/'.$account.'/albumid/'.$aID.'?kind=photo&access=public&thumbsize='.$thumb_width.'&imgmax='.$max_width); 
		$file = getContents('http://picasaweb.google.com/data/feed/api/user/'.$account.'/albumid/'.$aID.'?kind=photo&access=public&thumbsize='.$thumb_width.'&imgmax='.$max_width);
		
		$xml = new SimpleXMLElement($file); 
		$xml->registerXPathNamespace('media', 'http://search.yahoo.com/mrss/');
	   
		if(strpos($_SERVER['HTTP_HOST'],'photo-galleries')==true) {
		echo '<a href="' . DIR_REL . '/index.php/envision-it/photo-galleries/" id="returntoalbums"><< View All Galleries</a>';
		echo '<div>Current Album: "'.$xml->title.'"</div>';
		echo '<p>'.nl2br($xml->subtitle).'</p>'; 
		}
		
		
		if (sizeof($albums) > 1)
		{
			echo '<p><a href="javascript:history.go(-1);"
						title="'.t('Back to the gallery').'">'.t('Back to the gallery').'</a></p>';
		}
		
		foreach($xml->entry as $feed)
		{         
			$group = $feed->xpath('./media:group/media:thumbnail');
			
			$description = nl2br($feed->summary);
			
			$a = $group[0]->attributes();
			$b = $feed->content->attributes(); 
			
			echo '<div class="googpicwrap"><a href="'.$b['src'].'" title="'.$description.'" rel="gallery'.$aID.'" class="colorboxgalleryimage"><img src="'.$a['url'].'"';
			
			if ($alttags == 1)	   
				echo 'alt="'.$feed->title.'"';
				 
			echo 'class="image_google_picasa" /></a></div>';		
		}
		echo '<div class="clearfix"></div>';

	}
	else
	{ 
		//"http://picasaweb.google.com/data/feed/api/user/".$account."?kind=album&access=public&thumbsize=".$thumb_width
		// $file = $f->getContents("http://picasaweb.google.com/data/feed/api/user/".$account."?kind=album&thumbsize=".$thumb_width);
		$file = getContents("http://picasaweb.google.com/data/feed/api/user/".$account."?kind=album&thumbsize=".$thumb_width);
		$xml = new SimpleXMLElement($file);
		$xml->registerXPathNamespace('gphoto', 'http://schemas.google.com/photos/2007');
		$xml->registerXPathNamespace('media', 'http://search.yahoo.com/mrss/');
		$counter = 1;

		
		echo '<div style="font-size:90%;padding-left:8px;padding-bottom:15px;">Choose an album.</div><div class="clearfix"></div>';
		foreach($xml->entry as $feed)
		{
			$group = $feed->xpath('./media:group/media:thumbnail');
			$a = $group[0]->attributes();
			$id = $feed->xpath('./gphoto:id');								
		   
		    if (@in_array('all', (array)$albums) || @in_array($id[0], (array)$albums))
			{
				echo '<div class="googpicwrap"><a href="'.$_SERVER['REQUEST_URI'].'?aID='.$id[0].'">';
				echo '<img src="'.$a[0].'"';
				
				if ($alttags)	   
					echo ' alt="'.$feed->title.'"  title="'.$feed->title.'" ';
				
				echo 'class="image_google_picasa" /></a>';
				echo '<div class="gallerytitle">'.$feed->title.'</div><p>'.nl2br($feed->summary).'</p></div>';							
			}
			$counter++;
		}
		echo '<div class="clearfix"></div>';
	}


}


/* ================================================================================= */
/* Custom Code */
/* ================================================================================= */
/**
	 * Just a consistency wrapper for file_get_contents
	 * Should use curl if it exists and fopen isn't allowed (thanks Remo)
	 * @param $filename
	 */
	function getContents($file, $timeout = 5) {
		$url = @parse_url($file);
		if (isset($url['scheme']) && isset($url['host'])) {
			if (ini_get('allow_url_fopen')) {
				$ctx = stream_context_create(array( 
					'http' => array( 'timeout' => $timeout ) 
				)); 
				if ($contents = @file_get_contents($file, 0, $ctx)) {
					return $contents;
				}
			}
			
			if (function_exists('curl_init')) {
				$curl_handle = curl_init();
				curl_setopt($curl_handle, CURLOPT_URL, $file);
				curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, $timeout);
				curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
				$contents = curl_exec($curl_handle);
				$http_code = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
				if ($http_code == 404) {	
					return false;
				}
				
				return $contents;
			}
		} else {
			if ($contents = @file_get_contents($file)) {
				return $contents;
			}
		}
		
		return false;
	}

	function t($s){
		return $s;
	}

	// add_action('wp_enqueue_scripts', 'include_external_files');
	/*function include_external_files(){
		wp_enqueue_style('colorbox-css', plugin_dir_url(__FILE__) . 'colorbox/colorbox.css');
		wp_enqueue_script('colorbox-js', plugin_dir_url(__FILE__) . 'colorbox/jquery.colorbox.js');
	}*/

	add_action('admin_enqueue_scripts', 'include_style');
	function include_style(){
		wp_enqueue_style('wp-google-picasa-web-style-css', plugin_dir_url(__FILE__) . 'wp-google-picasa-web-style.css');
	}