<?php if (count($events)) { ?>
	<table class="eventor zebra">
		<thead>
		<tr>
			<th>Day</th>
			<th>Date</th>
			<th>Event</th>
			<th>Organising club</th>
			<th>Discipline</th>
		</tr>
		</thead>
		<tbody>
		<?php foreach ($events as $e) { ?>
			<tr>
				<td><?php echo $e->weekday; ?></td>
				<td class='evt-date'><?php echo $e->date; ?></td>
				<td><a target='_blank' href='http://eventor.orienteering.asn.au/Events/Show/<?php echo $e->id; ?>'><?php echo $e->title; ?></a></td>
				<td><?php echo $e->club; ?></td>
				<td><?php echo $e->discipline; ?></td>
			</tr>
		<?php } ?>
		</tbody>
	</table>
<?php } ?>
<a href="<?php echo add_query_arg('resetcache', 'yes');?>">Refresh data</a>


