<?php if (count($events)) { ?>
<table class="eventor zebra">
<thead>
<tr>
<th>Date</th>
<th>Club</th>
<th>Event</th>
<th>Results</th>
<th>Livelox</th>
</tr>
</thead>
<tbody>
<?php foreach ($events as $e) { ?>
<tr>
<td class='evt-date'><?php echo $e->date; ?></td>
<td><?php echo $e->club; ?></td>
<td><?php echo $e->title; ?></td>
<td><?php echo $e->results ? "<a target='_blank' href='http://eventor.orienteering.asn.au/Events/ResultList?eventId={$e->id}'>Results</a>" : ''; ?>
<td>
   <?php if (array_key_exists('Eventor_LiveloxEventConfigurations', $e->extras)) {
                    echo '<a target=_blank href="https://www.livelox.com/Events/Show/';
                    echo strtok($e->extras['Eventor_LiveloxEventConfigurations'], ',');
                    echo '">Livelox</a>';
                }?>
</td>
</tr>
<?php } ?>
</tbody>
</table>
<?php } ?>
<a href="<?php echo add_query_arg('resetcache', 'yes');?>">Refresh data</a>
