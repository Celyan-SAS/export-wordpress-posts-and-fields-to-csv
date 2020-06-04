/* CSV bouton download */
(function($) {
    $(document).ready(function() {
        
        var post_type = getUrlParameter('post_type');
        var post = getUrlParameter('post');
        
        //if nothing test if we are in users
        if(post == undefined && post_type == undefined){
            var result = window.location.href.search("users.php");
            if(result!=undefined && result>0){
                post_type = "WP_users";
            }
        }
        
        if(post == undefined && post_type!=undefined){
            
			//list_filters_export is setup at the init with the wp_localize_script
			var data_filter = {};
			$.each(list_filters_export,function(index,filter){				
				if(filter!=''){
					data_filter[filter] = getUrlParameter(filter);
				}
			});
						
            var data = {
                'action': 'add_button_admin_download_csv',
                'post_type': post_type,
				'data_filters': data_filter
            };
            jQuery.post(ajaxurl, data, function(response) {
                if(response){
                    $('.tablenav.bottom .bulkactions:last').append(response);
                }
            });
        }
        
    });
    
    
    
    function getUrlParameter(sParam) {
        var sPageURL = decodeURIComponent(window.location.search.substring(1)),
            sURLVariables = sPageURL.split('&'),
            sParameterName,
            i;

        for (i = 0; i < sURLVariables.length; i++) {
            sParameterName = sURLVariables[i].split('=');

            if (sParameterName[0] === sParam) {
                return sParameterName[1] === undefined ? true : sParameterName[1];
            }
        }
    }
    
})( jQuery );