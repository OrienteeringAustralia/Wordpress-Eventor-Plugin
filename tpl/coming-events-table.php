<?php if (count($events)) { ?>
	<table class="eventor zebra">
		<thead>
		<tr>
			<th>Day</th>
			<th></th>
			<th>Date</th>
			<th>Club</th>
			<th>Event</th>
			<th>Enter&nbsp;By</th>
			<th>Directions</th>
		</tr>
		</thead>
		<tbody>
		<?php foreach ($events as $e) { ?>
			<tr>
				<td><?php echo $e->weekday; ?></td>
				<td><?php 
				    echo "[ics_button subject='". $e->title . "'" . 
				         " description='" . $e->title . "   " . "http://eventor.orienteering.asn.au/Events/Show/" . $e->id . "'" .
				         " location=''" .
				         " start-date='" . $e->starttime . "'" .
				         " end-date='" . $e->endtime . "']" .
				         "<img src='/wp-content/plugins/ics-button/plugin/img/Calendar-Add.png' title='Add event to calendar' width=35 />" . 
				         "[/ics_button]"
				    ?>
				</td>
				<td class='evt-date'><?php echo $e->date; ?></td>
				<td><?php
			            $logoFile = wp_upload_dir()['basedir'] . "/clublogos/" . $e->club . ".png";
			            if (file_exists($logoFile)) {
				            echo "<img width=40 src=\"/wp-content/uploads/clublogos/" . $e->club . ".png\" title=\"$e->club\"/>"; 
			            }
				    ?></td>
				<td><a href='details/<?php echo $e->id; ?>'><?php echo $e->title; ?></a></td>
				<td class='evt-date'><?php
				        if (isset($e->entryDeadline)) {
				            $tz = new DateTimeZone(get_option('timezone_string'));
		                    $now = new DateTime(null, $tz);
		                    if ($now < $e->entryDeadline) {
    				            echo $e->entryDeadline->format('d-M-y');
		                    }
				        }
				    ?>
				</td>
				<td><?php
				        if ($e->location) {
				            echo "<a href='http://maps.apple.com/?daddr=" . (string) $e->location['y'] . "," . (string) $e->location['x'] . "'>Apple Maps</a><br/>";
				            echo "<a href='http://maps.google.com/?daddr=" . (string) $e->location['y'] . "," . (string) $e->location['x'] . "&saddr=Current%20Location'>Google Maps</a>";
				        }
				    ?></td>
			</tr>
		<?php } ?>
		</tbody>
	</table>
<?php } ?>
<a href="<?php echo add_query_arg('resetcache', 'yes');?>">Refresh data</a>
