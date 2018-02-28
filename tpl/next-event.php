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
		echo "<a href='/eventor/events/details/" . $e->id . "'>" . $e->title . "</a>";
        echo "</b>";
        if ($e->location) {
            echo " &nbsp;[Directions by <a href='http://maps.apple.com/?daddr=" . (string) $e->location['y'] . "," . (string) $e->location['x'] . "'>Apple Maps</a>, ";
            echo "<a href='http://maps.google.com/?daddr=" . (string) $e->location['y'] . "," . (string) $e->location['x'] . "&saddr=Current%20Location'>Google Maps</a>]";
        }
        echo "</center>";
	return; 
	}
}
?>
