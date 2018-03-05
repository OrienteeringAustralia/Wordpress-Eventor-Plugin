<?php

/**
 * Plugin Name: Eventor
 * Description: Integration with Eventor (http://eventor.orienteering.asn.au)
 * Version: 0.9.1
 * License: GPL3
 */
class Eventor {

	private $options;

	function __construct() {
		$this->options = get_option('evtr_settings');
		add_action('admin_menu', [$this, 'evtr_add_admin_menu']);
		add_action('admin_init', [$this, 'evtr_settings_init']);
		add_action('init', [$this, 'evtr_init'], 0);
		add_action('wp', [$this, 'evtr_controller']);
		add_action('save_post_eventor', [$this, 'evtr_save_meta']);
		add_action('widgets_init', [$this, 'evtr_load_widget']);
		add_action('wp_enqueue_scripts', array($this, 'evtr_enqueue_scripts' ));
	}

	// Register and load the widget
	function evtr_load_widget() {
		register_widget('evtr_widget');
	}

	function evtr_add_admin_menu() {
		add_options_page('Eventor Settings', 'Eventor', 'manage_options', 'eventor', [
			$this,
			'eventor_options_page',
		]);
	}

	function evtr_settings_exist() {
		if (false == get_option('eventor_settings')) {
			add_option('eventor_settings');
		}
	}

	function evtr_settings_init() {
		register_setting('eventorAPI', 'evtr_settings');
		add_settings_section('evtr_eventorAPI', __('API Credentials'), [
			$this,
			'evtr_settings_section_callback',
		], 'eventorAPI');
		add_settings_field('evtr_api_url', __('API URL'), [
			$this,
			'evtr_api_url_render',
		], 'eventorAPI', 'evtr_eventorAPI');
		add_settings_field('evtr_api_key', __('API Key'), [
			$this,
			'evtr_api_key_render',
		], 'eventorAPI', 'evtr_eventorAPI');
		add_settings_field('evtr_api_timeout', __('Timeout, seconds'), [
			$this,
			'evtr_api_timeout_render',
		], 'eventorAPI', 'evtr_eventorAPI');
	}

	function evtr_settings_section_callback() {
		//echo __('Login as a user on Eventor website and get an API key to use with this plugin.');
		delete_transient('evtr_organisations_');
	}

    function evtr_enqueue_scripts() {
        wp_enqueue_style('widget-stylesheet', plugin_dir_url(__FILE__) . 'eventor.css');
    }

	function evtr_api_url_render() { ?>
		<input type='text' class="regular-text code" name='evtr_settings[evtr_api_url]' value='<?php echo $this->options['evtr_api_url']; ?>' placeholder="http://...../api/" />
		<?php
	}

	function evtr_api_key_render() { ?>
		<input type='text' class="regular-text code" name='evtr_settings[evtr_api_key]' value='<?php echo $this->options['evtr_api_key']; ?>' />
		<?php
	}

	function evtr_api_timeout_render() { ?>
		<input type='text' class="small-text code" name='evtr_settings[evtr_api_timeout]' value='<?php echo $this->options['evtr_api_timeout']; ?>' />
		<?php
	}

	function eventor_options_page() { ?>
		<form action='options.php' method='post'>
			<h2>Eventor Settings</h2>
			<?php
			settings_fields('eventorAPI');
			do_settings_sections('eventorAPI');
			submit_button(); ?>
		</form>
		<?php
	}

	function evtr_init() {
		register_post_type('eventor', [
			'labels'               => [
				'name'               => 'Eventor lists',
				'singular_name'      => 'Eventor list',
				'menu_name'          => 'Eventor lists',
				'parent_item_colon'  => 'Parent Item:',
				'all_items'          => 'All Items',
				'view_item'          => 'View Item',
				'add_new_item'       => 'Add New Item',
				'add_new'            => 'Add New',
				'edit_item'          => 'Edit Item',
				'update_item'        => 'Update Item',
				'search_items'       => 'Search Item',
				'not_found'          => 'Not found',
				'not_found_in_trash' => 'Not found in Trash',
			],
			'label'                => 'eventor',
			'description'          => 'Eventor-based lists of events',
			'supports'             => ['title'],
			'hierarchical'         => false,
			'public'               => true,
			'show_ui'              => true,
			'show_in_menu'         => true,
			'show_in_nav_menus'    => true,
			'show_in_admin_bar'    => true,
			'menu_position'        => null,
			'can_export'           => false,
			'has_archive'          => false,
			'exclude_from_search'  => false,
			'publicly_queryable'   => true,
			'capability_type'      => 'page',
			'register_meta_box_cb' => [$this, 'evtr_add_metaboxes'],
		]);

		add_rewrite_tag('%eventor_splits%', '\d+');
		add_rewrite_tag('%eventor_details%', '\d+');
		add_rewrite_rule('eventor/([^/]+)/(details|splits)/(\d+)/?$', 'index.php?eventor=$matches[1]&eventor_$matches[2]=$matches[3]', 'top');
		
		add_filter( 'ocean_main_metaboxes_post_types', [$this, 'oceanwp_metabox'], 20 );
	}

    /**
     * Add the OceanWP Settings metabox in your CPT
     * Need this to customise the sidebar (otherwise it uses default sidebar) 
     */
    function oceanwp_metabox( $types ) {
    	// Your custom post type
    	$types[] = 'eventor';
    
    	return $types;
    }

	function evtr_add_metaboxes() {
		add_meta_box('eventorMetas', "Options", [$this, 'evtr_metabox_options_render'], 'eventor', 'normal');
	}

	function evtr_metabox_options_render($post, $args) {
		wp_enqueue_script('post-edit', plugin_dir_url(__FILE__) . 'post-edit.js');
		wp_enqueue_style('post-edit', plugin_dir_url(__FILE__) . 'post-edit.css');
		// Add an nonce field so we can check for it later.
		wp_nonce_field('eventor_meta_box', 'eventor_meta_box_nonce');

		$mode  = get_post_meta($post->ID, '_evtr_mode', true) ?: 'past';
		$year  = get_post_meta($post->ID, '_evtr_year', true);
		$tpl   = get_post_meta($post->ID, '_evtr_tpl', true);
		$clsid = get_post_meta($post->ID, '_evtr_classification', true);
		$tpls  = plugin_dir_path(__FILE__) . 'tpl';
		?>
		<table id="evtr-table" class="evtr-show-<?php echo $mode; ?>">
			<tbody>
			<tr>
				<th><label for="eventor_organisation"><?php _e('Organisation'); ?>: </label></th>
				<td><select name="eventor_organisation" id="eventor_organisation">
						<?php
						$api = new EventorAPI();
						echo implode("\n", $api->getOrganisationsOptions(get_post_meta($post->ID, '_evtr_organisation', true))); ?>
					</select></td>
			</tr>
			<tr>
				<th><label for="eventor_classification"><?php _e('Classification'); ?>: </label></th>
				<td><select name="eventor_classification[]" id="eventor_classification" multiple="multiple" size="6">
						<?php
						foreach (
							[
								1 => 'Championship events',
								2 => 'National events',
								3 => 'State events',
								4 => 'Local events',
								5 => 'Club events',
								6 => 'International events',
							] as $value => $text
						) {
							echo "<option value='{$value}'" . (in_array($value, $clsid) ? ' selected="selected"' : '') . ">{$text}</option>\n";
						} ?>
					</select>
					<i>If none selected, all events are displayed</i></td>
			</tr>
			<tr>
				<th><label for="eventor_tpl"><?php _e('Template'); ?>: </label></th>
				<td><select name='eventor_tpl' id="eventor_tpl">
						<?php
						if (is_dir($tpls)) {
							foreach (glob($tpls . '/*.php') as $tpath) {
								$t = pathinfo($tpath, PATHINFO_FILENAME);
								echo "<option value='{$t}' " . selected($tpl, $t) . ">{$t}</option>\n";
							}
						} ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="eventor_mode"><?php _e('Mode'); ?>: </label></th>
				<td><select name='eventor_mode' id="eventor_mode">
						<option value='past' <?php selected($mode, 'past'); ?>><?php _e('Past events'); ?></option>
						<option value='future' <?php selected($mode, 'future'); ?>><?php _e('Future events'); ?></option>
					</select>
				</td>
			</tr>
			<tr class="evtr-past">
				<th><label for="eventor_year"><?php _e('Year'); ?>: </label></th>
				<td><select name="eventor_year" id="eventor_year"><?php
				        echo "<option value='lastyear' " . selected($year, 'lastyear') . ">past 365 days</option>";
						$current = (int) date('Y');
						foreach (range($current, $current - 20) as $y) {
							echo "<option value='{$y}' " . selected($year, $y) . ">{$y}</option>\n";
						} ?></select>
				</td>
			</tr>
			<tr class="evtr-future">
				<th><label for="eventor_range"><?php _e('Range'); ?>: </label></th>
				<td>
					<input type='number' class="small-text code" name='eventor_range' id="eventor_range" value='<?php echo get_post_meta($post->ID, '_evtr_range', true); ?>' />
					<i>How many days ahead</i></td>
			</tr>
			<tr>
				<th><label for="eventor_count"><?php _e('Count'); ?>: </label></th>
				<td>
					<input type='number' class="small-text code" name='eventor_count' id="eventor_count" value='<?php echo get_post_meta($post->ID, '_evtr_count', true); ?>' />
					<i>How many events, 0 for unlimited</i></td>
			</tr>
			<tr>
				<th><label for="eventor_extraids"><?php _e('ExtraIDs'); ?>: </label></th>
				<td>
					<input type='text' class="regular-text code" name='eventor_extraids' id="eventor_extraids" value='<?php echo get_post_meta($post->ID, '_evtr_extraids', true); ?>' />
					<i>Comma delimited list of extra event ids to include (eg. a NSW event that is close to Canberra)</i></td>
			</tr>
			<tr>
				<th><label for="eventor_disciplinefilter"><?php _e('Discipline Filter'); ?>: </label></th>
				<td><select name="eventor_disciplinefilter" id="eventor_disciplinefilter">
						<?php
						$api = new EventorAPI();
						echo implode("\n", $api->getDisciplineOptions(get_post_meta($post->ID, '_evtr_disciplinefilter', true))); ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="eventor_titlefilter"><?php _e('Title Filter'); ?>: </label></th>
				<td>
					<input type='text' class="regular-text code" name='eventor_titlefilter' id="eventor_titlefilter" value='<?php echo get_post_meta($post->ID, '_evtr_titlefilter', true); ?>' />
					<i>Only display events that include this text in the title</i></td>
			</tr>
			</tbody>
		</table>
		<?php
	}

	function evtr_save_meta($post_id) { // - Update the post's metadata.

		update_post_meta($post_id, '_evtr_organisation', isset($_REQUEST['eventor_organisation']) ? (int) $_REQUEST['eventor_organisation'] : 0);
		update_post_meta($post_id, '_evtr_classification', isset($_REQUEST['eventor_classification']) ? $_REQUEST['eventor_classification'] : []);

		if (isset($_REQUEST['eventor_mode'])) {
			$mode = sanitize_text_field($_REQUEST['eventor_mode']);
			if (in_array($mode, ['past', 'future'])) {
				update_post_meta($post_id, '_evtr_mode', $mode);
			}
		}

		if (isset($_REQUEST['eventor_year'])) {
		    // removed cast to int, was: $year    = (int) $_REQUEST['eventor_year'];
			$year    = $_REQUEST['eventor_year'];
			// removed the checking to allow 'lastyear' to be stored
			//$current = (int) date('Y');
			//if (in_array($year, range($current, $current - 30))) {
				update_post_meta($post_id, '_evtr_year', $year);
			//}
		}

		if (isset($_REQUEST['eventor_range'])) {
			update_post_meta($post_id, '_evtr_range', (int) $_REQUEST['eventor_range']);
		}

		if (isset($_REQUEST['eventor_count'])) {
			update_post_meta($post_id, '_evtr_count', (int) $_REQUEST['eventor_count']);
		}

		if (isset($_REQUEST['eventor_extraids'])) {
			update_post_meta($post_id, '_evtr_extraids', $_REQUEST['eventor_extraids']);
		}

		if (isset($_REQUEST['eventor_titlefilter'])) {
			update_post_meta($post_id, '_evtr_titlefilter', $_REQUEST['eventor_titlefilter']);
		}

		update_post_meta($post_id, '_evtr_disciplinefilter', isset($_REQUEST['eventor_disciplinefilter']) ? (int) $_REQUEST['eventor_disciplinefilter'] : 0);

		if (isset($_REQUEST['eventor_tpl'])) {
			$tpl = sanitize_text_field($_REQUEST['eventor_tpl']);
			if (is_dir($tpls = plugin_dir_path(__FILE__) . 'tpl')) {
				foreach (glob($tpls . '/*.php') as $tpath) {
					if ($tpath == $tpls . '/' . $tpl . '.php') {
						update_post_meta($post_id, '_evtr_tpl', $tpl);
					}
				}
			}
		}

	}

	function evtr_controller() {
		if ('eventor' == get_post_type()) {
			global $wp_query;
			if (array_key_exists('eventor_splits', $wp_query->query_vars)) {
			    $resultsByCourse = isset($_REQUEST['bycourse']); //view results by course rather than by class
				$api = new EventorAPI();
				$e   = new eventResult($api->getData('results/event', 
				            [
        						'eventId'           => $wp_query->query_vars['eventor_splits'],
		        				'includeSplitTimes' => 'true', 
				        	],
					        isset($_REQUEST['resetcache']) ? 0 : 2592000 // 30 days
				        ),!$resultsByCourse); 
				//var_dump_pre($e);
				$html =  '<!DOCTYPE html><html><head>';
				$html .= '<link rel="stylesheet" href="' . plugin_dir_url(__FILE__) . 'splits.css">';
				$html .= "<script>(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)})(window,document,'script','https://www.google-analytics.com/analytics.js','ga');ga('create', 'UA-106221840-1', 'auto');ga('send', 'pageview');</script>";
				$html .= '</head>';
				$html .= '<body>';
				$html .= '<img src="http://act.orienteering.asn.au/wp-content/uploads/2017/08/OACT-Logo-150px-x-150px.png" align="left" />';
				$html .= '<h1>' . $e->name . '</h1>';
				$html .= '<p class="eventinfo">' . $e->startDate->format('l, d F Y') . '<br/>';
				$html .= 'Total Entries: ' . $e->totalEntries() . '<br/>';
				$html .= 'Total Participants: ' . $e->totalParticipants() . '<br/>';
				$html .= '<a href="http://eventor.orienteering.asn.au/Events/ResultList?eventId=' . $e->id . '&groupBy=EventClass">Results on Eventor</a><br/>';
				$html .= '<a href="http://eventor.orienteering.asn.au/Events/Show/' . $e->id . '">Event Info on Eventor</a><br/>';
				//$html .= '<a href="' . $e->url . '">Event Info</a><br/>';
				if ($e->comment != "") {
    				$html .= 'Comment: ' . $e->comment . '<br/>';
				}
				$html .= '</p>';

                $protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
                $url = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
                $url = explode("?", $url)[0];
                
                if ($resultsByCourse) {
    				$html .= '<p><a href="' . $url . '">Results by Class</a> | Results by Course</p>';
                } else {
    				$html .= '<p>Results by Class | <a href="' . $url . '?bycourse">Results by Course</a></p>';
                }
				$html .= '<br clear="left">' . $e->display();
				
                $html .= '<br/><p>';
                $html .= '<b>MP:&nbsp;</b> Mispunch<br/>';
                $html .= '<b>DNS:</b> Did not start<br/>';
                $html .= '<b>DNF:</b> Did not finish<br/>';
                $html .= '<b>NTR:</b> No Time Recorded<br/>';
                $html .= '<b>DSQ:</b> Disqualified<br/>';
                $html .= '<b>OT&nbsp;:</b> Overtime<br/>';
                $html .= '</p>';

				$html .= '</body>';
				die($html);

			} elseif (array_key_exists('eventor_details', $wp_query->query_vars)) {
				add_filter('the_content', [$this, 'evtr_add_event_details']);
				add_filter('the_title', [$this, 'evtr_update_pagetitle'], 20);
				add_filter('wp_title', [$this, 'evtr_update_wptitle'], 5, 2);

			} else {
				add_filter('the_content', [$this, 'evtr_add_events_list']);
				add_filter('the_title', [$this, 'evtr_update_pagetitle'], 20);
			}
		}
	}

	function evtr_add_event_details($content) {
	    
	    function object_to_array($obj) {
            $_arr = is_object($obj) ? get_object_vars($obj) : $obj;
            foreach ($_arr as $key => $val) {
                    $val = (is_array($val) || is_object($val)) ? object_to_array($val) : $val;
                    $arr[$key] = $val;
            }
            return $arr;
        }

		if (is_single() && 'eventor' == get_post_type()) {
			global $wp_query;
			$tpl               = plugin_dir_path(__FILE__) . 'tpl/details.php';
			$api               = new EventorAPI();
			$xml               = $api->getData('event/' . $wp_query->query_vars['eventor_details'], [], isset($_REQUEST['resetcache']) ? 0 : 86400);
			$clubs             = $api->getOrganisations();
			$disciplines       = $api->getDisciplines();
			$event             = new stdClass;
			$event->id         = (int) $xml->EventId;
			$event->date       = getDateTime($xml->StartDate);
			$event->title      = (string) $xml->Name;
			$event->club       = $clubs[(int) $xml->Organiser->Organisation->OrganisationId];
			
			/* some debugging stuff
			$event->clubid     = $xml->Organiser->OrganisationId;
			$event->organiser  = $xml->Organiser;
			*/
			
			$event->discipline = implode(', ', array_intersect_key($disciplines, array_flip((array) $xml->DisciplineId)));
			$event->raceid     = (int) $xml->EventRace->EventRaceId;
			$event->distance   = (string) $xml->EventRace->attributes()->raceDistance;
			$event->light      = (string) $xml->EventRace->attributes()->raceLightCondition;
			$event->location   = isset($xml->EventRace->EventCenterPosition) ? [
				'lng' => (float) $xml->EventRace->EventCenterPosition->attributes()->x,
				'lat' => (float) $xml->EventRace->EventCenterPosition->attributes()->y,
			] : ['lat' => 'n/a', 'lng' => 'n/a'];

	    	/// Start and end time in iCal format
            $event->starttime = $event->date->format('Ymd') . "T" . $event->date->format("His");
            $enddate = getDateTime($xml->FinishDate);
            $event->endtime = $enddate->format('Ymd') . "T" . $enddate->format("His");
            
            if (isset($xml->EntryBreak->ValidToDate)) {
                $event->entryDeadline = getDateTime($xml->EntryBreak->ValidToDate);
            }
            
            // $event->officials = [];
            // foreach ($xml->EventOfficial as $eo) {
            //     $event->officials[$eo->EventOfficialId] = 
            // }
            
			$event->extras = [];
			if (isset($xml->HashTableEntry)) {
				foreach ($xml->HashTableEntry as $h) {
					$event->extras[(string) $h->Key] = (string) $h->Value;
				}
			}
			
			// contact data
			if (isset($xml->ContactData)) {
			    foreach ($xml->ContactData as $c) {
			        foreach ($c->Tele as $t) {
    			        if (isset($t->attributes()->phoneNumber)) {
        		            $event->phoneNumber = (string) $t->attributes()->phoneNumber;
    			        }
    			        if (isset($t->attributes()->mailAddress)) {
    			            $event->mailAddress = $t->attributes()->mailAddress;
    			        }
			        }
			    }
			}
			
			if (isset($xml->EventOfficial)) {
    			$event->officials = [];
			    foreach ($xml->EventOfficial as $o) {
			        $roleName = $api->getRoleName($o->RoleTypeId);
			        $personName = $api->getPersonName($o->PersonId, $roleName, $event->title);
			        
			        // var_dump_pre($personName); break;
			        
			        if ($personName && $roleName) {
			            if (isset($event->officials[$roleName])) {
    			            $event->officials[$roleName] .= ', ' . $personName;
			            } else {
        			        $event->officials[$roleName] = $personName;
			            }
			        }
			    }
			}

            // get documents
            $documents = $api->getDocuments($event->id, isset($_REQUEST['resetcache']) ? 0 : 86400);
            if (isset($documents) && count($documents) > 0) {
                $event->documents = [];
                foreach ($documents->Document as $doc) {
                    $event->documents[(string) $doc->attributes()->name] = $doc->attributes()->url;
                }
            }


            // pass a string to UI so we can see the xml structure
            //$the_array = object_to_array($xml);
            //$event->xmlstr = print_r($the_array, true);

			ob_start();
			require $tpl;
			$content = ob_get_contents();
			ob_end_clean();
		}

		return $content;
	}

	function evtr_update_pagetitle($title) {
		global $wp_query;
		if (in_the_loop() && is_single() && 'eventor' == get_post_type()) {
		    return ''; /// don't display a title
		    /// there's some change in the theme that means that the title was displaying (as a huge H1), and this seems the only way to delete it
// 			static $event_title;
// 			if ( ! isset($event_title)) {
// 				$api         = new EventorAPI();
// 				$xml         = $api->getData('event/' . $wp_query->query_vars['eventor_details']);
// 				$event_title = (string) $xml->Name;
// 			}

// 			return $event_title; 
		} else {
			return $title;
		}
	}

	function evtr_update_wptitle($title, $sep) {
		global $wp_query;
		if (is_single() && 'eventor' == get_post_type()) {
			static $event_title;
			if ( ! isset($event_title)) {
				$api         = new EventorAPI();
				$xml         = $api->getData('event/' . $wp_query->query_vars['eventor_details']);
				$event_title = "$xml->Name $sep ";
			}

			return $event_title;
		} else {
			return $title;
		}
	}

	function evtr_add_events_list($content) {

		if (is_single() && 'eventor' == get_post_type()) {
			$post     = get_post();
			$tz       = new DateTimeZone(get_option('timezone_string'));
			$now      = new DateTime(null, $tz);
			$range    = (int) get_post_meta($post->ID, '_evtr_range', true);
			$count    = (int) get_post_meta($post->ID, '_evtr_count', true);
			$orgid    = (int) get_post_meta($post->ID, '_evtr_organisation', true) ?: 4; //OACT as default
			$clsid    = get_post_meta($post->ID, '_evtr_classification', true);
			$mode     = get_post_meta($post->ID, '_evtr_mode', true);
			$extraids = get_post_meta($post->ID, '_evtr_extraids', true);
			$titlefilter = get_post_meta($post->ID, '_evtr_titlefilter', true);
			$tpl      = plugin_dir_path(__FILE__) . ('tpl/' . get_post_meta($post->ID, '_evtr_tpl', true) ?: 'default') . '.php';

			$resetcache = isset($_REQUEST['resetcache']);

			if (file_exists($tpl)) {

				if ($mode == 'past') {
					$cache    = $resetcache ? 0 : 86400;
					$todate   = min([$now, new DateTime("{$year}-12-31", $tz)]);
					$todate->modify("+1 day");

                    // allows using the last 365 days, rather than a single year
					$year = get_post_meta($post->ID, '_evtr_year', true);
					if ($year == 'lastyear') {
    					$fromdate = clone $todate;
	    				$fromdate->modify("-365 day");
					} else {
    					$year     = (int) get_post_meta($post->ID, '_evtr_year', true) ?: $now->format('Y');
    					$fromdate = new DateTime("{$year}-01-01", $tz);
					}
				} elseif ($mode == 'future') {
					$cache    = $resetcache ? 0 : 3600;
					$fromdate = new DateTime(null, $tz);
					$fromdate->modify('-2 day');
					$todate = new DateTime(null, $tz);
					$todate->modify("+{$range} day");
				} else {
					return $content;
				}

				$params = [
					'fromDate'        => $fromdate->format('Y-m-d'),
					'toDate'          => $todate->format('Y-m-d'),
					'organisationIds' => $orgid,
					'includeEntryBreaks' => "true",
					//'includeAttributes' => "true",
				];
				
				if ($clsid) {
					$params['classificationIds'] = implode(',', $clsid);
				}

				$api = new EventorAPI();
				$xml = $api->getData('events', $params, $cache);

				if ($xml) {
					//var_dump_pre($xml);
					$clubs       = $api->getOrganisations();
					$disciplines = $api->getDisciplines();
					$events      = $startdates = [];

					foreach ($xml->Event as $e) {
						//if ($series) if (!preg_match('#'.$series.'#i', (string)$e->Name)) continue;

						$startdate = getDateTime($e->StartDate);
						$enddate = getDateTime($e->FinishDate);

                        if (strlen($titlefilter) > 0) {
                            if (!preg_match('/' . $titlefilter . '/i',(string)$e->Name)) continue;
                        }

                        if (isExpiredEvent($e, $startdate, $mode, $now)) continue;
                        
                        $event = parseEvent($e, $startdate, $enddate, $clubs, $disciplines);

						if ($mode == 'past') {
							$event->results = false;
							if (isset($e->HashTableEntry)) {
								foreach ($e->HashTableEntry as $h) {
									if ((string) $h->Key == "officialResult_{$event->raceid}") {
										$event->results = true;
									}
									//				if (preg_match('#officialResult_(\d)+#',(string)$h->Key)) $event->results = true;
								}
							}

                            // don't show 5 day old events that don't have results                             
                            if ($event->results == false) {
                    			$finishdatetime = clone($startdate);
                    			$finishdatetime->modify('+ 5 day ');
                    			$finishdatetime->setTime(3, 0);
                    			if ($finishdatetime < $now) {
                    				continue;
                    			}
                            }
						}

						$events[]      = $event;
						$startdates[]  = $startdate;
					}
				}

    			if ($extraids) {
        			$api = new EventorAPI();
        			$xml = $api->getData('events', array('eventIds' => $extraids), $cache);
        			if ($xml) {
        			    
    					foreach ($xml->Event as $e) {
                		    $startdate = getDateTime($e->StartDate);
                		    $enddate = getDateTime($e->FinishDate);
    
                            if (isExpiredEvent($e, $startdate, $mode, $now)) continue;

                            $event = parseEvent($e, $startdate, $enddate, $clubs, $disciplines);
        					$events[]      = $event;
        					$startdates[]  = $startdate;
        
        					if ($mode == 'past') {
        						$event->results = false;
        						if (isset($e->HashTableEntry)) {
        							foreach ($e->HashTableEntry as $h) {
        								if ((string) $h->Key == "officialResult_{$event->raceid}") {
        									$event->results = true;
        								}
        								//				if (preg_match('#officialResult_(\d)+#',(string)$h->Key)) $event->results = true;
        							}
        						}
        					}
        				}
        			}
    			}
    			
    			if ($events) {
        			array_multisort($startdates, ($mode == 'past' ? SORT_DESC : SORT_ASC), $events);
        			if ($count) {
        				$events = array_slice($events, 0, $count);
        			}
					ob_start();
        			require $tpl;
					$content .= ob_get_contents();
					ob_end_clean();
                } else {
					echo _e('No events found');
                }
			}
		}

		return $content;
	}

	static function getTime($s) {
		return $s ? floor($s / 60) . ':' . str_pad($s % 60, 2, '0', STR_PAD_LEFT) : '-';
	}

	static function sortbyrank($a, $b) {
		if (null === $a->position) {
			return 1;
		}
		if (null === $b->position) {
			return - 1;
		}
		if ($a->position == $b->position) {
			return 0;
		}

		return ($a->position > $b->position) ? 1 : - 1;
	}

	static function sortbyelapsed($a, $b) {
		if ((null === $a->elapsed) or ($a->status != "OK")) {
			return 1;
		}
		if ((null === $b->elapsed) or ($b->status != "OK")) {
			return -1;
		}
		if ($a->elapsed == $b->elapsed) {
			return 0;
		}

		return ($a->elapsed > $b->elapsed) ? 1 : - 1;
	}

}

class evtr_widget extends WP_Widget {

	function __construct() {
		parent::__construct(
			'evtr_widget',
			'Eventor Widget',
			[
				'description' => 'Displays an Eventor-based events list in a sidebar widget',
			]);
	}

	public function widget($args, $instance) {

		$title = apply_filters('widget_title', $instance['title']);
		echo $args['before_widget'];
		/// Commented out the title - don't need another title
		//if ( ! empty($title)) {
		//	echo $args['before_title'] . $title . $args['after_title'];
		//}

		$resetcache = isset($_REQUEST['resetcache']);
		if (isset($instance['eventlist'])) {
			$post     = get_post($instance['eventlist']);
			$tz       = new DateTimeZone(get_option('timezone_string'));
			$now      = new DateTime(null, $tz);
			$range    = (int) get_post_meta($post->ID, '_evtr_range', true);
			$count    = (int) get_post_meta($post->ID, '_evtr_count', true);
			$orgid    = (int) get_post_meta($post->ID, '_evtr_organisation', true) ?: 4; //OACT as default
			$clsid    = get_post_meta($post->ID, '_evtr_classification', true);
			$mode     = get_post_meta($post->ID, '_evtr_mode', true);
			$extraids = get_post_meta($post->ID, '_evtr_extraids', true);
			$tpl      = plugin_dir_path(__FILE__) . 'tpl/' . (get_post_meta($post->ID, '_evtr_tpl', true) ?: 'default') . '.php';
			$disciplinefilter = get_post_meta($post->ID, '_evtr_disciplinefilter', true);
			$titlefilter = get_post_meta($post->ID, '_evtr_titlefilter', true);

			if ($mode == 'past') {
				$cache    = $resetcache ? 0 : 86400;
				$year     = (int) get_post_meta($post->ID, '_evtr_year', true) ?: $now->format('Y');
				$fromdate = new DateTime("{$year}-01-01", $tz);
				$todate   = min([$now, new DateTime("{$year}-12-31", $tz)]);
			}
			elseif ($mode == 'future') {
				$cache    = $resetcache ? 0 : 3600;
				$fromdate = new DateTime(null, $tz);
				$fromdate->modify('-2 day');
				$todate = new DateTime(null, $tz);
				$todate->modify("+{$range} day");
			}

			$params = [
				'fromDate'        => $fromdate->format('Y-m-d'),
				'toDate'          => $todate->format('Y-m-d'),
				'organisationIds' => $orgid,
			];

			if ($clsid) {
				$params['classificationIds'] = implode(',', $clsid);
			}

			$api = new EventorAPI();
			$xml = $api->getData('events', $params, $cache);

			$clubs       = $api->getOrganisations();
			$disciplines = $api->getDisciplines();

			$events = $startdates = [];


			if ($xml) {
				//var_dump($xml);

				foreach ($xml->Event as $e) {
					//if ($series) if (!preg_match('#'.$series.'#i', (string)$e->Name)) continue;
					if ($disciplinefilter != 0) {
					    if ($e->DisciplineId != $disciplinefilter) continue;
					}
					
                    if (strlen($titlefilter) > 0) {
                        if (!preg_match('/' . $titlefilter . '/i',(string)$e->Name)) continue;
                    }

            		$startdate = getDateTime($e->StartDate);
            		$enddate = getDateTime($e->FinishDate);

                    if (isExpiredEvent($e, $startdate, $mode, $now)) continue;

                    $event = parseEvent($e, $startdate, $enddate, $clubs, $disciplines);
					$events[]      = $event;
					$startdates[]  = $startdate;


					if ($mode == 'past') {
						$event->results = false;
						if (isset($e->HashTableEntry)) {
							foreach ($e->HashTableEntry as $h) {
								if ((string) $h->Key == "officialResult_{$event->raceid}") {
									$event->results = true;
								}
								//				if (preg_match('#officialResult_(\d)+#',(string)$h->Key)) $event->results = true;
							}
						}
					}
				}
			}

			if ($extraids) {
    			$api = new EventorAPI();
    			$xml = $api->getData('events', array('eventIds' => $extraids), $cache);
    			if ($xml) {
    			    
					foreach ($xml->Event as $e) {
            		    $startdate = getDateTime($e->StartDate);
            		    $enddate = getDateTime($e->FinishDate);

                        if (isExpiredEvent($e, $startdate, $mode, $now)) continue;
    
                        $event = parseEvent($e, $startdate, $enddate, $clubs, $disciplines);
    					$events[]      = $event;
    					$startdates[]  = $startdate;
    
    					if ($mode == 'past') {
    						$event->results = false;
    						if (isset($e->HashTableEntry)) {
    							foreach ($e->HashTableEntry as $h) {
    								if ((string) $h->Key == "officialResult_{$event->raceid}") {
    									$event->results = true;
    								}
    								//				if (preg_match('#officialResult_(\d)+#',(string)$h->Key)) $event->results = true;
    							}
    						}
    					}
    				}
    			}
			}

			if ($events) {
    			array_multisort($startdates, ($mode == 'past' ? SORT_DESC : SORT_ASC), $events);
    			if ($count) {
    				$events = array_slice($events, 0, $count);
    			}
    			require $tpl;
            }
		} else {
			echo "Please select item to display";
		}
		echo $args['after_widget'];
	}

	public function form($instance) { ?>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>">Title:</label>
			<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr(isset($instance['title']) ? $instance['title'] : 'Upcoming events'); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('eventlist'); ?>">Event list:</label>
			<select class="widefat" id="<?php echo $this->get_field_id('eventlist'); ?>" name="<?php echo $this->get_field_name('eventlist'); ?>">
				<?php foreach (
					get_posts([
						'post_type'   => 'eventor',
						'numberposts' => - 1,
					]) as $item
				) {
					echo '<option' . ((isset($instance['eventlist']) && $instance['eventlist'] == $item->ID) ? ' selected="selected"' : '') . ' value="' . $item->ID . '">' . $item->post_title . '</option>';
				} ?>
			</select>
		</p>
		<?php
	}

// Updating widget replacing old instances with new
	public function update($new_instance, $old_instance) {
		return [
			'title'     => ! empty($new_instance['title']) ? strip_tags($new_instance['title']) : '',
			'eventlist' => ! empty($new_instance['eventlist']) ? strip_tags($new_instance['eventlist']) : '',
		];
	}
}

//**********************
class EventorAPI {
	private $url;
	private $key;
	private $timeout;

	function __construct() {
		$options       = get_option('evtr_settings');
		$this->url     = $options['evtr_api_url'];
		$this->key     = $options['evtr_api_key'];
		$this->timeout = $options['evtr_api_timeout'];
	}

	function getDisciplines() {
		return [
			1 => "Foot orienteering",
			2 => "Mountain bike orienteering",
			3 => "Ski orienteering",
			4 => "Trail orienteering",
			5 => "Radio orienteering",
			6 => "Park and street orienteering",
		];
    }
    
    function getDisciplineOptions($selected = 0) {
		$options = [];
		$options[] = '<option value="0"' . ($selected == '0' ? ' selected="selected"' : '') . '>ALL</option>';
		
		$disciplines = $this->getDisciplines();
		foreach ($disciplines as $d_id => $d_name) {
    		$options[] = '<option value="' . $d_id . '"' . ($selected == $d_id ? ' selected="selected"' : '') . '>' . $d_name . '</option>';
		}

		return $options;
	}


	function getPersonName($personId, $roleName, $eventTitle) {
	    
	    // load the persons name from the database
	    $personName = get_option("evtr_psn_" . $personId);
	    if ($personName) {
	        return $personName;
	    }
	    
	    // if we can't find them, then call eventor api to get list of competitors
// 		if ($competitorList = $this->getData('competitors', ['organisationId' => 4])) {
// 	        foreach($competitorList->Competitor as $c) {
// 	            if ((int) $c->Person->PersonId == (int) $personId) {
//     	            $personName = $c->Person->PersonName->Given . ' ' . $c->Person->PersonName->Family;

//             		// store it!
//             		add_option("evtr_psn_" . $personId, $personName, '', 'no');
//     	            break;
// 	            }
// 	        }
// 		}

        
 		if ($personDetails = $this->getData('competitor/' . $personId, [])) {
 		    $personName = $personDetails->Person->PersonName->Given . ' ' . $personDetails->Person->PersonName->Family;
 		    add_option("evtr_psn_" . $personId, $personName, '', 'no');
        } else { 
            // we get 403 if the person isn't in OACT. No workaround possible. Asked eventor people to add an api to work this out, still waiting...
            // only thing we can do is manually add an entry in the database. 
            //    Table is: oact_options
            //    Option_Name is: evtr_psn_<personid>, eg. evtr_psn_482
            //    Option_Value is: <Person Name>, eg. John Smith
            write_log("Unknown person: " . $personId . " for role: " . $roleName . ", event: " . $eventTitle);
        }
		
		return $personName;
	}
	
    function getDocuments($eventId, $usecache = 86400) {
        return $this->getData('events/documents', ['eventIds'=> $eventId], $usecache);
    }


	function getOrganisations() {
		$clubs = [];
		if ($clubsxml = $this->getData('organisations')) {
			foreach ($clubsxml->Organisation as $c) {
				$clubs[(int) $c->OrganisationId] = (string) $c->Name;
			}
		}

		return $clubs;
	}

	function getOrganisationsOptions($selected = 0) {
		$options = [];
		if ($xml = $this->getData('organisations')) {

			$orgs   = [0 => 'All'];
			$levels = [0];

			foreach ($xml->Organisation as $o) {
				$id   = (int) $o->OrganisationId;
				$name = (string) $o->Name;
				$pid  = isset($o->ParentOrganisation) ? (int) $o->ParentOrganisation->OrganisationId[0] : 0;

				$l            = $levels[$id] = $levels[$pid] + 1;
				$kids[$pid][] = $id;
				$orgs[$id]    = str_repeat('&nbsp;', 3 * $l) . $name;
			}

			$selected = (int) $selected;
			$order    = [];
			self::sortlist($kids, 0, $order);
			foreach ($order as $i) {
				$options[] = '<option value="' . $i . '"' . ($selected == $i ? ' selected="selected"' : '') . '>' . $orgs[$i] . '</option>';
			}

		}

		return $options;
	}

	private static function sortlist($a, $i, &$list) {
		$list[] = $i;
		if (isset($a[$i])) {
			foreach ($a[$i] as $k) {
				self::sortlist($a, $k, $list);
			}
		}
	}

	function getData($action, $options = [], $usecache = 2592000) { //30 days

		$q = $action . ($options ? '?' . http_build_query($options) : '');

		$transname = "evtr_{$action}_" . implode('|', $options);

		//cache here
		if ($usecache) {
			if ($v = get_transient($transname)) {
				libxml_use_internal_errors(true);
				try {
					$xml = simplexml_load_string($v);
					if ($errors = libxml_get_errors()) {
						foreach ($errors as $error) {
							error_log($error->message);
						}
						libxml_clear_errors();
					} else {
						return $xml;
					}
				}
				catch (Exception $e) {
					error_log($e->getMessage());
				}
				//return new SimpleXMLElement($v);
			}
		}

        //write_log('Requesting URL: ' . $this->url . $q);
        
		$ch = curl_init($this->url . $q);
		curl_setopt_array($ch, [
			CURLOPT_FAILONERROR    => true,
			CURLOPT_CONNECTTIMEOUT => (int) $this->timeout,
			//CURLOPT_LOW_SPEED_TIME	=> $timeout,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_HTTPHEADER     => ["ApiKey: " . $this->key],
		]);

		$raw = curl_exec($ch);
		$e   = curl_error($ch);
		curl_close($ch);

		if ($e) {
			//throw new Exception(200, "Eventor API error - {$e}");
			error_log("Eventor API error: {$e}. URL: " . $this->url . $q);

			return false;
		}

		if ($usecache) {
		    set_transient($transname, $raw, $usecache);
		} else {
		    set_transient($transname, $raw, 86400); // default to 1 day
		}

		return new SimpleXMLElement($raw);
	}
	
	function getRoleName($roleId) {
	    //return "Official (" . $roleId . ")";
	    switch ($roleId){
	        case 101: return "Event director"; break;
	        case 102: return "Course planner"; break;
	        case 103: return "Contact person"; break;
	        case 104: return "Event controller"; break;
	        case 105: return "Course controller"; break;
	        default: return false;
	    }
	}
}

new Eventor;

function extractCourse($classResult) {
    $parts = explode(":", $classResult);
    return $parts[0];    
}

class eventResult {
	public $id;
	public $name = '';
	public $eventClassId;
	public $eventStatusId;
	public $startDate;
	public $finishDate;
	public $url;
	public $comment;
	public $punchType;
	public $modifyDate;
	public $modifiedBy;

	public $classes = [];

	function __construct($xml, $byClasses = true) {
		$Event = $xml->Event;
		if (isset($Event->EventId)) {
			$this->id = (int) $Event->EventId;
		}
		if (isset($Event->Name)) {
			$this->name = (string) $Event->Name;
		}
		if (isset($Event->EventClassificationId)) {
			$this->eventClassId = (int) $Event->EventClassificationId;
		}
		if (isset($Event->EventStatusId)) {
			$this->eventStatusId = (int) $Event->EventStatusId;
		}
		if (isset($Event->WebURL)) {
			$this->url = (string) $Event->WebURL;
		}
		if (isset($Event->Comment)) {
			$this->comment = (string) $Event->Comment;
		}
		$this->punchType  = (string) $xml->PunchingUnitType['value'];
		$this->startDate  = getDateTime($Event->StartDate);
		$this->finishDate = getDateTime($Event->FinishDate);
		$this->modifyDate = getDateTime($Event->ModifyDate);
		$this->ModifiedBy = (int) $Event->ModifiedBy->PersonId;

		foreach ($xml->ClassResult as $classResult) {
		    // byClasses is normal way of displaying results
            if ($byClasses) {
    			$cl = new classResult($classResult);
    			$cl->processSplits();
    			$cl->processRanks();
    			$this->classes[] = $cl;
            } else {
                // this is by course. eg. combine all Orange 1 classes into single table
    		    $courseName = extractCourse((string) $classResult->EventClass->Name);
    		    $existingClass = $this->findExistingClass($courseName);
    		    if ($existingClass != null) {
    		        $existingClass->addMoreResults($classResult);
    		    } else {
        			$cl = new classResult($classResult);
        			$cl->name = $courseName;
        			$cl->shortName = $courseName;
        			$this->classes[] = $cl;
    		    }
            }
		}
		if (!$byClasses) {
		    foreach ($this->classes as $class) {
		        $class->processSplits();
		        $class->processRanks();
		    }
		}
		
		/*
		print_r($xml);
		print('<p>');*/
	}

	function display() {
		$html = '';
		foreach ($this->classes as $class) {
		    $html .= '<h3>' . $class->name;
		    if ($class->name != $class->shortName) {
		        $html .= ' (' . $class->shortName . ')';
		    }
		    $html .= '</h3>';
		    $html .= '<p>Entries: ' . $class->getEntryCount();
		    if ($class->getEntryCount() != $class->getParticipantCount()) {
		        $html .= '<br/>Participants: ' . $class->getParticipantCount(); 
		    }
		    // 100px for name, 40 for club, 45 for total time. Each column is 50px. Plus one for finish split.
			$html .= '</p><table class="evt-results" width="' . (185 + 50 * (count($class->personResults[0]->splits) + 1) )  . 'px">' . $class->display() . '</table>';
		}

		return $html;
	}
	
	function totalEntries() {
	    $totalEntries = 0;
		foreach ($this->classes as $class) {
		    $totalEntries += $class->getEntryCount();
		}
		return $totalEntries;
	}

	function totalParticipants() {
	    $totalEntries = 0;
		foreach ($this->classes as $class) {
		    $totalEntries += $class->getParticipantCount();
		}
		return $totalEntries;
	}
	
	function findExistingClass($courseName) {
		foreach ($this->classes as $class) {
            $thisCourseName	= extractCourse($class->name);
            if ($thisCourseName == $courseName) {
                return $class;
            }
		}
        return null;
	}

}

class classResult {
	public $id;
	public $name;
	public $shortName;
	public $personResults = [];

	//public $course = [];

	function __construct($xml) {
		$this->id        = (string) $xml->EventClass->EventClassId;
		$this->name      = (string) $xml->EventClass->Name;
		$this->shortName = (string) $xml->EventClass->ClassShortName;

		foreach ($xml->PersonResult as $person) {
			$this->personResults[] = new personResult($person);
		}

		usort($this->personResults, ['Eventor', 'sortbyrank']);
	}
	
	function addMoreResults($xml) {
		foreach ($xml->PersonResult as $person) {
			$this->personResults[] = new personResult($person);
		}
		usort($this->personResults, ['Eventor', 'sortbyelapsed']);
	}

	function display() {
		$html = '<thead><tr><th colspan="3"></th>';

		foreach ($this->personResults as $i => $result) {
			if ( ! $i) {
				$html .= $result->getCourseHeader() . '</tr></thead><tbody>';
			}
			$html .= $result->display();
		}

		return $html . '</tbody>';
	}

	function processSplits() {
		foreach ($this->personResults as $result) {
			$prev = 0;
			foreach ($result->splits as $split) {
				if ($split->time) {
					$split->split = $split->time - $prev;
					$prev         = $split->time;
				} else {
					$split->split = null;
				}
			}
		}
	}

	function processRanks() {
		foreach (array_keys($this->personResults[0]->splits) as $col) {
			$trank = $srank = [];
			foreach ($this->personResults as $i => $row) { 
			    if ($row->status == 'OK') {
    				if (isset($row->splits[$col]->time)) {
    				    if ($row->splits[$col]->time != "-") {
        					$trank[$i] = $row->splits[$col]->time;
        					$srank[$i] = $row->splits[$col]->split;
    				    }
    				}
			    }
			}

			$trank = array_unique($trank); //sharing rank when time is equal
			sort($trank);
			$trank = array_flip(array_values($trank));

			$srank = array_unique($srank);
			sort($srank);
			$srank = array_flip(array_values($srank));

			foreach ($this->personResults as $i => $row) {
			    if ($row->status == 'OK') {
    				if (isset($row->splits[$col]->time)) {
    				    if (isset($trank[$row->splits[$col]->time])) {
    	    				$row->splits[$col]->trank = 1 + $trank[$row->splits[$col]->time];
    				    }
    				    if (isset($row->splits[$col]->split)) {
        					$row->splits[$col]->srank = 1 + $srank[$row->splits[$col]->split];
    				    }
    				}
			    }
			}
		}
	}
	
	function getParticipantCount() {
	    $peopleCount = count($this->personResults);

        // looks for the / separator between team members to work out how many participants. eg. One Person / Other Person
	    $extras = "";
        foreach ($this->personResults as $i => $row) {
            $extras .= $row->firstName;
            $extras .= $row->surName;
        }
	    $extrasCount = substr_count($extras, "/");

	    return $peopleCount + $extrasCount;
	}
	
	function getEntryCount() {
	    if (stristr($this->name, 'team')) {
	        $finishTimes = [];
			foreach ($this->personResults as $i => $row) {
			    $finishTimes[] = $row->elapsed;
            }
	        return count(array_unique($finishTimes));
    	} else {
    	    return count($this->personResults);
    	}
	}
}

class personResult {
	public $id;
	public $firstName = '';
	public $surName = 'Anon';
	public $club;
	public $siNumber;
	public $sex;
	public $start;
	public $finish;
	public $status = 'MisPunch';
	public $elapsed;
	public $position;

	public $splits = [];

	function __construct($xml) {
		$this->club = new club($xml->Organisation);
		if (isset($xml->PersonId)) {
			$this->id = (int) $xml->PersonId;
		}
		if (isset($xml->Person->PersonName->Given)) {
			$this->firstName = (string) $xml->Person->PersonName->Given;
		}
		if (isset($xml->Person->PersonName->Family)) {
			$this->surName = (string) $xml->Person->PersonName->Family;
		}
		if (isset($xml->Person["sex"])) {
			$this->sex = $xml->Person["sex"];
		}

		$Result        = $xml->Result;
		$this->start   = getDateTime($Result->StartTime);
		$this->finish  = getDateTime($Result->FinishTime);
		$this->elapsed = getSeconds($Result->Time);

		if (isset($Result->ResultPosition)) {
			$this->position = (int) $Result->ResultPosition;
		}
		if (isset($Result->CompetitorStatus["value"])) {
			$this->status = (string) $Result->CompetitorStatus["value"];
			switch ($this->status){
    	        case "MisPunch":     $this->status = "MP"; break;
    	        case "DidNotStart":  $this->status = "DNS"; break;
    	        case "DidNotFinish": $this->status = "DNF"; break;
    	        case "Inactive":     $this->status = "NTR"; break;
    	        case "Disqualified": $this->status = "DSQ"; break;
    	        case "OverTime":     $this->status = "OT"; break;
    	    }
		}
		$this->siNumber = (int) $Result->CCard->CCardId;

		foreach ($Result->SplitTime as $split) {
			$this->splits[] = new evSplit($split);
		}

		//Add finish split...
		$fSplit           = new evSplit();
		$fSplit->sequence = null;
		$fSplit->code     = 'F';
		$fSplit->time     = $this->elapsed;

		$this->splits[] = $fSplit;
	}

	function display() {
		$html = '<tr class="classResult ' . $this->status . '"><th class="personName" width="100px">' . $this->firstName . ' ' . $this->surName . '</th><th width="40px">' . $this->club . '</th><th width="45px">';
		$html .= ($this->status == 'OK' ? Eventor::getTime($this->elapsed) : $this->status) . '</th>';

		foreach ($this->splits as $split) {
			if ($split->time) {
				$html .= '<td><div class="r r' . $split->trank . '">' . Eventor::getTime($split->time) . ($split->trank ? ' (' . $split->trank . ')' : '') . '</div>
							  <div class="s s' . $split->srank . '">' . Eventor::getTime($split->split) . ($split->srank ?' (' . $split->srank . ')' : '') . '</div></td>';
			} else {
				$html .= '<td><div class="r">-</div><div class="s">-</div></td>';
			}
		}
		$html .= '</tr>';

		return $html;
	}

	function getCourseHeader() {
		$html = '';
		foreach ($this->splits as $split) {
			$html .= '<th width="50px">' . $split->sequence . '<br/>' . $split->code . '</th>';
		}

		return $html;
	}
}

class club {
	public $id;
	public $name = '';
	public $country = '';

	function __construct($xml) {
		if (isset($xml->ClubId)) {
			$this->id = (int) $xml->ClubId;
		}
		if (isset($xml->ShortName)) {
			$this->name = (string) $xml->ShortName;
		} elseif (isset($xml->Name)) {
			$this->name = (string) $xml->Name;
		}
		if (isset($xml->CountryId["value"])) {
			$this->country = (string) $xml->CountryId["value"];
		}
	}

	function __toString() {
		return $this->name;
	}
}

class evSplit {
	public $sequence;
	public $code;
	public $time = 0;
	public $split;
	public $trank;
	public $srank;

	function __construct($xml = null) {
		if (isset($xml["sequence"])) {
			$this->sequence = (int) $xml["sequence"];
		}
		if (isset($xml->ControlCode)) {
			$this->code = (int) $xml->ControlCode;
		}
		if (isset($xml->Time)) {
			$this->time = getSeconds((string) $xml->Time);
		}
	}
}

// SOME UTILITY FUNCTIONS


function isExpiredEvent($e, $startdate, $mode, $now) {
	if ($mode == 'future') {
		$finishdatetime = clone($startdate);
		$finishdatetime->modify('+ 1 day ');
		$finishdatetime->setTime(3, 0);
		if ($finishdatetime < $now) {
			return true; //screening events finished yesterday
		}
	}
}

function parseEvent($e, $startdate, $enddate, $clubs, $disciplines) {
	$event          = new stdClass;
	$event->id      = (string) $e->EventId;
	$event->weekday = $startdate->format('l');
	$event->date    = $startdate->format('d-M-y');
	
	/// Start and end time in iCal format
    $event->starttime = $startdate->format('Ymd') . "T" . $startdate->format("His");
    $event->endtime = $enddate->format('Ymd') . "T" . $enddate->format("His");

    if (isset($e->EntryBreak->ValidToDate)) {
        $event->entryDeadline = getDateTime($e->EntryBreak->ValidToDate);
    }

	$event->title = (string) $e->Name;
	$event->club  = $clubs[(int) $e->Organiser->OrganisationId];
	foreach ($e->DisciplineId as $d) {
		$event->discipline[] = isset($disciplines[(int) $d]) ? $disciplines[(int) $d] : 'Unknown';
	}
	$event->discipline = implode(',<br />', $event->discipline);
	$race = $e->EventRace->attributes();
	$event->distance = $race->raceDistance;
	//		$event->light		= $race->raceLightCondition;
	//		$event->location	= isset($e->EventRace->EventCenterPosition) ? $e->EventRace->EventCenterPosition->attributes() : false;
	$event->location = isset($e->EventRace->EventCenterPosition) ? $e->EventRace->EventCenterPosition->attributes() : false;
	$event->raceid = (string) $e->EventRace->EventRaceId;
	return $event;
}

function getDateTime($d, $tz = null) {
	static $utc;
	if ( ! $utc) {
		$utc = new DateTimeZone('UTC');
	}
	if (isset($d->Date) && isset($d->Clock)) {
		$date = new DateTime((string) $d->Date . ' ' . (string) $d->Clock, $utc);
		$date->setTimezone(new DateTimeZone(get_option('timezone_string')));

		return $date;
	}
}

function getSeconds($t) {
    if (preg_match('#(\d+):(\d+):?(\d+)?#', $t, $matches)) {
       switch ( sizeof($matches) ) {
            case 1:
                 return 0;
            case 2:
                 return $matches[1];
            case 3:
                 return $matches[1] * 60 + $matches[2];
            case 4:
                 return $matches[1] * 3600 + $matches[2] * 60 + $matches[3];
       }
    }
}

if (!function_exists('write_log')) {
    function write_log ( $log )  {
        if ( true === WP_DEBUG ) {
            if ( is_array( $log ) || is_object( $log ) ) {
                error_log( print_r( $log, true ) );
            } else {
                error_log( $log );
            }
        }
    }
}

function var_dump_pre($mixed = null) {
  echo '<pre>';
  var_dump($mixed);
  echo '</pre>';
  return null;
}

