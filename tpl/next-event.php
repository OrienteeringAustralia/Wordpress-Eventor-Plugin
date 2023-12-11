<?php 
if (count($events)) { 
	foreach ($events as $e) {
	    echo "<center><b><font color='#FA6301'>NEXT EVENT: </font></b>";
        $logoFile = wp_upload_dir()['basedir'] . "/clublogos/" . $e->club . ".png";
        if (file_exists($logoFile)) {
            echo "<img width=40 src=\"/wp-content/uploads/clublogos/" . $e->club . ".png\" title=\"$e->club\"/>"; 
        }
        echo "<b style='font-size: 110%;'>";
		echo $e->weekday;
	    echo ", ";
	    echo $e->date;
	    echo " : &nbsp;";
		echo "<span style='white-space: nowrap;'><a href='/eventor/events/details/" . $e->id . "'>" . $e->title . "</a></span>";
        echo "</b>";
        if ($e->location) {
            echo " &nbsp;<span style='white-space: nowrap; font-size: 90%;'>[Directions by <a href='http://maps.apple.com/?daddr=" . (string) $e->location['y'] . "," . (string) $e->location['x'] . "'>Apple Maps</a>, ";
            echo "<a href='https://www.google.com/maps/dir/?api=1&destination=" . (string) $e->location['y'] . "," . (string) $e->location['x'] . "'>Google Maps</a>]</span>";
        }
        echo "</center>";
	return; 
	}
}
?>
