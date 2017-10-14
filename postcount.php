<?php
include 'simple_html_dom.php';
if(!$_GET)die("Please use an id with parameter ?userid=youruserid");
if(!$_GET['userid'])die("Please enter a user ID!");
if(!IS_NUMERIC($_GET['userid']))die("Please use numeric ID!");
$nopointsforums = array(
        'SPAM/Testing' => true,
        'Introductions' => true,
);
/**
 * Writes the given text with a border into the image using TrueType fonts.
 * @author John Ciacia
 * @param image An image resource
 * @param size The font size
 * @param angle The angle in degrees to rotate the text
 * @param x Upper left corner of the text
 * @param y Lower left corner of the text
 * @param textcolor This is the color of the main text
 * @param strokecolor This is the color of the text border
 * @param fontfile The path to the TrueType font you wish to use
 * @param text The text string in UTF-8 encoding
 * @param px Number of pixels the text border will be
 * @see http://us.php.net/manual/en/function.imagettftext.php
 */
function imagettfstroketext(&$image, $size, $angle, $x, $y, &$textcolor, &$strokecolor, $fontfile, $text, $px) {

    for($c1 = ($x-abs($px)); $c1 <= ($x+abs($px)); $c1++)
        for($c2 = ($y-abs($px)); $c2 <= ($y+abs($px)); $c2++)
            $bg = imagettftext($image, $size, $angle, $c1, $c2, $strokecolor, $fontfile, $text);

   return imagettftext($image, $size, $angle, $x, $y, $textcolor, $fontfile, $text);
}
//end

$userpage = file_get_html('http://freevps.us/user-'.$_GET["userid"].'.html');
$username = $userpage->find('span[class="largetext"]', 0)->plaintext; // find the username from the user profile page.
//refer to http://simplehtmldom.sourceforge.net/manual.htm

$url="http://freevps.us/search.php?action=finduser&uid=".$_GET["userid"];
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false );
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Must be set to true so that PHP follows any "Location:" header
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$a = curl_exec($ch); // $a will contain all headers

$url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL); // This is what you need, it will return you the last effective URL

// Uncomment to see all headers
/*
echo "<pre>";
print_r($a);echo"<br>";
echo "</pre>";
*/

//echo $url; // Voila
/* the above code was taken from http://stackoverflow.com/questions/20233115/how-to-get-destination-url-from-redirection-url-in-php
when clicking on "Find more posts" it appears the resulting search page that only returns the user's posts is dynamically generated*/

$numberofpoststhismonth = 0;
$uncountedposts = 0;
$numberofpostsnotmadeinthismonth = 0;
$pagenumber = 1;
$firstrun = True;
while (($numberofpostsnotmadeinthismonth === 0 and $numberofpoststhismonth != 0) or $firstrun === True) {
        if ($pagenumber === 1) {
                $firstrun = False;
        }
        $html = file_get_html($url.'&sortby=dateline&order=desc&uid=&page='.$pagenumber);
        $rows = 0;
        foreach($html->find('table[class="tborder"] tr') as $element)
        {

		// Ignore first two rows.
                if ($rows++ < 2)
                    continue;
                $post = $element->find('*[style="white-space: nowrap; text-align: center;"]',0);
                $forum = $element->find('a', 3)->innertext;
				
		// Forum doesn't give points.
				if (isset($nopointsforums[$forum])) {
                                        $uncountedposts++;
					continue;
				}
				if (!is_string($post->plaintext)) {
					continue;
				}
                
				if (substr($post->plaintext, 0, 3) === 'Yes' or substr($post->plaintext, 0, 3) === 'Tod' or substr($post->plaintext, 0, 3) === date("m-")) {
					$numberofpoststhismonth++;

/* http://transfusion.cf/tf-content/uploads/2013/12/screenshot_134.png We are crawling the webpage for the timestamps of the posts - if they begin with YESterday or TODay or the current month.- 
If all the posts on the first search page are made this month, $pagenumber++ */
         }
				else {
					$numberofpostsnotmadeinthismonth++;
				}
	}
	$totalposts = $uncountedposts+$numberofpoststhismonth;
	if (($totalposts %= 20) != 0) {
                $numberofpostsnotmadeinthismonth++;
        }
        $pagenumber++;
}

header('Pragma: public');
header('Cache-Control: max-age=240');
header('Expires: '. gmdate('D, d M Y H:i:s \G\M\T', time() + 240));
if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])){ 
  // if the browser has a cached version of this image, send 304 
  header('Last-Modified: '.$_SERVER['HTTP_IF_MODIFIED_SINCE'],true,304); 
  exit; 
} 
// Force the browser to cache the image for 4 minutes. Crawling webpages like that is not exactly good etiquette.

header("Content-Type: image/png");
$img = imagecreatefrompng("./freevpsbg.png"); // the background image.
$font_color = imagecolorallocate($img, 255, 255, 255);
$stroke_color = imagecolorallocate($img, 0, 0, 0);
$posts_color = imagecolorallocate($img, 221, 255, 0);
imagettfstroketext($img, 8, 0, 50, 13, $font_color, $stroke_color, "./visitor1.ttf", $username."'s posts: ", 1);
imagettfstroketext($img, 8, 0, 173, 13, $posts_color, $stroke_color, "./visitor1.ttf", $numberofpoststhismonth, 1);
imagettfstroketext($img, 8, 0, 183, 13, $font_color, $stroke_color, "./visitor1.ttf", "/20", 1);
if ($numberofpoststhismonth >= 40) {
        $message="posessed poster!";
        $message_color = imagecolorallocate($img, 111, 255, 0);
} elseif ($numberofpoststhismonth >= 30) {
        $message="Sociable Seal!";
        $message_color = imagecolorallocate($img, 0, 255, 222);
} elseif ($numberofpoststhismonth >= 20) {
        $message="Completed! Chewsum5gum";
        $message_color = imagecolorallocate($img, 0, 255, 0);
} elseif ($numberofpoststhismonth >= 17) {
        $message="OH! LIVIN ON A PRAYER";
        $message_color = imagecolorallocate($img, 255, 100, 0);
} elseif ($numberofpoststhismonth >=15) {
        $message="WOAH, HALFWAY THERE";
        $message_color = imagecolorallocate($img, 255, 255, 0);
} elseif ($numberofpoststhismonth >= 0) {
        $message="Dry your tears n Post";
        $message_color = imagecolorallocate($img, 255, 0, 0);
}
imagettfstroketext($img, 10, 0, 50, 26, $message_color, $stroke_color, "./visitor1.ttf", $message, 1);
// http://www.johnciacia.com/2010/01/04/using-php-and-gd-to-add-border-to-text/
imagepng($img);
imagedestroy($img);
// free memory associated with this image - delete it from the PHP cache
//echo $numberofpoststhismonth;
//echo date("m-d");
?>
