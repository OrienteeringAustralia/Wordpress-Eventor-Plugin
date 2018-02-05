<?php if (count($events)) { ?>
	<table class="eventor zebra">
		<thead>
		<tr>
			<th>Date</th>
			<th>Club</th>
			<th>Event</th>
			<!--th>Splits</th-->
			<th>Results</th>
			<th>Feedback</th>
		</tr>
		</thead>
		<tbody>
		<?php foreach ($events as $e) { ?>
		<tr>
			<td class='evt-date'><?php echo $e->date; ?></td>
			<td><?php
			            $logoFile = wp_upload_dir()['basedir'] . "/clublogos/" . $e->club . ".png";
			            if (file_exists($logoFile)) {
				            echo "<img width=40 src=\"/wp-content/uploads/clublogos/" . $e->club . ".png\" title=\"$e->club\"/>"; 
			            } else write_log("EVENTOR. Club logo missing for: " . $e->club);
			    ?>
			</td>
			<td><!--a target='_blank' href='http://eventor.orienteering.asn.au/Events/Show/<?php echo $e->id; ?>'--><?php echo $e->title; ?><!--/a--></td>
			<td><?php echo $e->results ? "<a target='_blank' href='splits/{$e->id}'>results</a>" : ''; ?></td>
			<!--td><?php echo $e->results ? "<a target='_blank' href='http://eventor.orienteering.asn.au/Events/ResultList?eventId={$e->id}&groupBy=EventClass'>results<img width='12' src='/wp-content/uploads/clublogos/external_link.png'/></a>" : ''; ?></td-->
			<td><a href='/contact/feedback/course-feedback/?eventname=<?php 
			    $eventname = $e->title;
			    $eventname = str_replace('&','and',$eventname);
			    $eventname = str_replace('#','',$eventname);
			    echo $eventname; 
			?>'>feedback</a></td>
		</tr>
		<?php } ?>
		</tbody>
	</table>
<?php } ?>
<a href="<?php echo add_query_arg('resetcache', 'yes');?>">Refresh data</a>


