<a href="/events">Back to Events List</a>
<br/>
<div class="eventor">

		    <?php
			    $logoFile = wp_upload_dir()['basedir'] . "/clublogos/" . $event->club . ".png";
			    if (file_exists($logoFile)) {
				    echo "<img align='right' width=80 src=\"/wp-content/uploads/clublogos/" . $event->club . ".png\" title=\"$event->club\"/>"; 
			    }
			?>


	<h2><?= $event->title ?></h2>
	<dl>
		<dt>Date</dt>
		<dd><?= $event->date->format('j F Y') ?></dd>
		<?php
		    if (isset($event->entryDeadline)) {
		        echo "<dt>Entry Deadline</dt><dd>";
		        $entryDeadline = $event->entryDeadline->format('j F Y');
		        
		        $tz = new DateTimeZone(get_option('timezone_string'));
		        $now = new DateTime(null, $tz);
		        $deadlinedatetime = clone($event->entryDeadline);
    			$deadlinedatetime->modify('- 3 day ');
	    		$deadlinedatetime->setTime(3, 0);
	    		if ($now > $event->entryDeadline) {
	    		    echo "Pre entry closed";
	    		} else {
    		    	if ($deadlinedatetime < $now) {
    			    	echo '<font color=red>' . $entryDeadline . '</font>';
    			    } else {
    			    	echo $entryDeadline;
    			    }
                    echo "&nbsp;&nbsp;&nbsp;<a target='_blank' href='http://eventor.orienteering.asn.au/Entry?eventId=" . $event->id . "'>Enter Now</a>";
	    		}	        
		        echo "</dd>";
		    }
		?>
		<?php 
		    if (isset($event->extras['Eventor_EntryOnTheDay'])) {
    		    if ($event->extras['Eventor_EntryOnTheDay'] == '1') {
    		        echo "<dt>Enter on the day</dt>";
    		        echo "<dd>Available</dd>";
    		    }
    		    if ($event->extras['Eventor_EntryOnTheDay'] == '0') {
    		        echo "<dt>Enter on the day</dt>";
    		        echo "<dd>Not Available</dd>";
    		    }
    		    if ($event->extras['Eventor_EntryOnTheDay'] == '2') {
    		        echo "<dt>Enter on the day</dt>";
    		        echo "<dd>Available for a limited number of classes</dd>";
    		    }
		    }
		?>
		<dt>Calendar</dt>
		<dd><?php 
			    echo "[ics_button subject='". $event->title . "'" . 
				     " description='" . $event->title . "   " . "http://eventor.orienteering.asn.au/Events/Show/" . $event->id . "'" .
				     " location=''" .
				     " start-date='" . $event->starttime . "'" .
				     " end-date='" . $event->endtime . "']" .
				     "<img src='/wp-content/plugins/ics-button/plugin/img/Calendar-Add.png' title='Add event to calendar' width=20> Download iCal Calendar</img>" . 
				     "[/ics_button]"
		?></dd>
		<dt>Organising Club</dt>
		<dd><?= $event->club ?></dd>
		<!--dt>Discipline(s)</dt>
		<dd>< ?= $event->disciplines ?></dd-->
		<!--dt>Race ID</dt>
		<dd>< ?= $event->raceid ?></dd-->
		<dt>Distance</dt>
		<dd><?= $event->distance ?></dd>
		<!--dt>Light conditions</dt>
		<dd>< ?= $event->light ?></dd-->
		
		
	    <?php if (isset($event->documents)) { ?>
            <dt>Documents</dt>
        	<dd>
                <?php
                    //var_dump_pre($event->documents);
                    $doneFirstOne = false;
                    foreach ($event->documents as $key => $value) {
                        if ($doneFirstOne) {
        		            echo "<br/>";
                        }
            	        echo "<a href='" . $value . "'><img src='/wp-content/uploads/2017/08/document-icon.png' width=20/> " . $key . "</a>";
            	        $doneFirstOne = true;
                    }
                ?>
            </dd>
        <?php } ?>

		<?php
		    if (isset($event->extras['Eventor_Message'])) {
		        echo "<dt>Event Notes</dt>";
		        $message = $event->extras['Eventor_Message'];
		        $message = preg_replace('~(?:www|http://|https://)\S+~', '<a href="$0">$0</a>', $message);
		        $message = str_replace("---", "</p><p>", $message);
		        echo "<dd><p>" . $message . "</p></dd>";
		    }
		?>
		
		<?php
		    if (isset($event->extras['Eventor_EntryTermsAndConditions'])) {
		        echo "<dt>Terms and conditions</dt>";
		        echo "<dd>" . $event->extras['Eventor_EntryTermsAndConditions'] . "</dd>";
		    }
		?>
		
		<?php if ($event->location && $event->location['lat'] != 'n/a') { ?>
    		<dt>Driving Directions</dt>
            <dd><a href='http://maps.apple.com/?daddr=<?= $event->location['lat'] ?>,<?= $event->location['lng'] ?>'><img src="/wp-content/uploads/2017/08/AppleMaps.png" width=25/> Using Apple Maps</a>
            <br/><a href='http://maps.google.com/?daddr=<?= $event->location['lat'] ?>,<?= $event->location['lng'] ?>&saddr=Current%20Location'><img src="/wp-content/uploads/2017/08/GoogleMaps.png" width=25/> Using Google Maps</a></dd>
		<?php } ?>
		
		<!--dd>< ?= $event->location['lat'] ?>, < ?= $event->location['lng'] ?></dd-->
		<dt>Eventor</dt>
		<dd><a target='_blank' href='http://eventor.orienteering.asn.au/Events/Show/<?= $event->id ?>'>View event on Eventor (opens in a new tab)</a></dd>
	</dl>
	
	<?php if (isset($event->phoneNumber) || isset($event->mailAddress)) { ?>
        <br/>
    	<h3>Event Contact Information</h3>
    	<dl>
    		<?php 
    		    if (isset($event->phoneNumber)) {
    		        echo "<dt>Phone</dt>";
    		        echo "<dd>" . $event->phoneNumber . "</dd>";
    		    }
    
    		    if (isset($event->mailAddress)) {
    		        echo "<dt>Email</dt>";
    		        echo "<dd>" . $event->mailAddress . "</dd>";
    		    }
    		    
    		    if (isset($event->officials)) {
            		foreach ($event->officials as $key => $value) { 
        		        echo "<dt>" . $key . "</dt>";
            	        echo "<dd>" . $value . "</dd>";
        		    }
    		    }
            ?>
    	</dl>
    <?php } ?>

    <?php if (isset($event->location) && $event->location['lat'] != 'n/a') { ?>
        <br/>
        <h3>Location</h3>
        <iframe width="100%" height="400" style="border: 0px solid rgb(0, 0, 0);" src="https://www.google.com/maps?q=<?= $event->location['lat'] ?>,<?= $event->location['lng'] ?>&amp;z=18&amp;t=m&amp;output=embed"></iframe>
	<?php } ?>
	
	<!--?php echo "<pre>" . $event->xmlstr . "</pre>"; ?-->
</div>

<!--?php var_dump($event); ?-->
<br/>
<a href="<?php echo add_query_arg('resetcache', 'yes');?>">Refresh data</a>
