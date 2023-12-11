<?php if (count($events)) { ?>
	<table class="eventor zebra">
		<thead>
		<tr>
			<th>Date</th>
			<th>Event</th>
			<th>Directions</th>
		</tr>
		</thead>
		<tbody>
		<?php foreach ($events as $e) { ?>
			<tr>
				<td class='evt-date'><?php echo $e->date; ?></td>
				<td><a href='/eventor/events/details/<?php echo $e->id; ?>'><?php echo $e->title; ?></a></td>
				<td><?php
				        if ($e->location) {
				            echo "<a href='http://maps.apple.com/?daddr=" . (string) $e->location['y'] . "," . (string) $e->location['x'] . "'>Apple Maps</a><br/>";
				            echo "<a href='https://www.google.com/maps/dir/?api=1&destination=" . (string) $e->location['y'] . "," . (string) $e->location['x'] . "'>Google Maps</a>";
				        }
				    ?></td>
			</tr>
		<?php } ?>
		</tbody>
	</table>
<?php } ?>
<a href="<?php echo add_query_arg('resetcache', 'yes');?>">Refresh data</a>
