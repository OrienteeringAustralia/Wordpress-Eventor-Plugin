<?php
if (count($events)) { ?>

<table class="eventor zebra">
<thead><tr><th>Date</th><th>Event Name</th><th>Event Type</th><th>Results</th><th>Splits</th><th title="The beaten track">TBT</th></tr></thead>
<tbody>
<?php foreach ($events as $e) {
	$results = $e->results ? "<a target='_blank' href='http://eventor.orienteering.asn.au/Events/ResultList?eventId={$e->id}&groupBy=EventClass'>results</a>" : '';
	$splits	= $e->results ? "<a target='_blank' href='?post_type=eventor&amp;splits={$e->id}'>splits</a>" : '';
	$tbt	= $e->results ? "<a target='_blank' href='http://thebeatentrack.org/map.html?id={$e->id}&raceid={$e->raceid}'>tbt</a>" : '';
	echo "<tr><td class='evt-date'>{$e->date}</td><td><a target='_blank' href='http://eventor.orienteering.asn.au/Events/Show/{$e->id}'>{$e->title}</a></td><td>{$e->discipline}</td><td>{$results}</td><td>{$splits}</td><td>{$tbt}</td></tr>";
} ?>
</tbody></table>
<?php } ?>
<a href="<?php echo add_query_arg('resetcache', 'yes');?>">Refresh data</a>

