<?php if (count($events)) { ?>
<ul class="line">
<?php foreach ($events as $e) echo "<li>{$e->date} <a target='_blank' href='http://eventor.orienteering.asn.au/Events/Show/{$e->id}'>{$e->title}</a></li>"; ?>
</ul>
<?php } ?>
<a href="<?php echo add_query_arg('resetcache', 'yes');?>">Refresh data</a>


