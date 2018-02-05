/**
 * Created by kostya on 17/09/14.
 */
jQuery(document).ready(function($){

	$('#eventor_mode').change(function(){
		$("#evtr-table").attr( "class", 'evtr-show-' + $(this).val());
	});
});
