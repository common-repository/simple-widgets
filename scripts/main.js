(function(g){var i=/radio|checkbox/i,j=/[^\[\]]+/g,k=/^[\-+]?[0-9]*\.?[0-9]+([eE][\-+]?[0-9]+)?$/,l=function(b){if(typeof b=="number")return true;if(typeof b!="string")return false;return b.match(k)};g.fn.extend({formParams:function(b,d){if(!!b===b){d=b;b=null}if(b)return this.setParams(b);else if(this[0].nodeName.toLowerCase()=="form"&&this[0].elements)return jQuery(jQuery.makeArray(this[0].elements)).getParams(d);return jQuery("input[name], textarea[name], select[name]",this[0]).getParams(d)},setParams:function(b){this.find("[name]").each(function(){var d=b[g(this).attr("name")],a;if(d!==undefined){a=g(this);if(a.is(":radio"))a.val()==d&&a.attr("checked",true);else if(a.is(":checkbox")){d=g.isArray(d)?d:[d];g.inArray(a.val(),d)>-1&&a.attr("checked",true)}else a.val(d)}})},getParams:function(b){var d={},a;b=b===undefined?false:b;this.each(function(){var e=this;if(!((e.type&&e.type.toLowerCase())=="submit"||!e.name)){var c=e.name,f=g.data(e,"value")||g.fn.val.call([e]),h=i.test(e.type);c=c.match(j);e=!h||!!e.checked;if(b){if(l(f))f=parseFloat(f);else if(f==="true")f=true;else if(f==="false")f=false;if(f==="")f=undefined}a=d;for(h=0;h<c.length-1;h++){a[c[h]]||(a[c[h]]={});a=a[c[h]]}c=c[c.length-1];if(a[c]){g.isArray(a[c])||(a[c]=a[c]===undefined?[]:[a[c]]);e&&a[c].push(f)}else if(e||!a[c])a[c]=e?f:undefined}});return d}})})(jQuery);

jQuery( document ).ready( function( $ ) {
    $( '.sw_submit' ).click( function() {
        var data = $( '.sw_form' ).formParams()['simple_widgets_data'];   
        
        $.ajax( {
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: { data: data, action: 'sw_save_settings' },
            async: true,
            beforeSend: function( jqXHR, settings ) {
                $( '.sw_submit' ).attr( 'disabled', 'disabled' );
            },
            success: function( data, textStatus, jqXHR ) {
                if ( 1 == data.res ) {
                    window.location.href = data.redirect;
                }
                
                else {
                    $( '.sw_submit' ).removeAttr( 'disabled' );
                }
            },
            error: function( jqXHR, textStatus, errorThrown ) {
                $( '.sw_submit' ).removeAttr( 'disabled' );
            }
        } );     
    } );
        
    $( '.sidebar-data' ).click( function() { 
        if ( $( '+ .sidebar-widgets .widget-entry', this ).size() )
            $( '~ .sidebar-widgets', this ).slideToggle( 125, 'easeInOutQuad' );
    } );
        
    $( '.widget-data' ).click( function() { 
        $( '~ .widget-settings', this ).slideToggle( 125, 'easeInOutQuad' );
    } );
    
    /**
    * Open appropriate box.
    */
    
    var selected = $( window.location.hash + '_aby_sw' );
    
    if ( selected.size() ) {
        selected.parents( '.sidebar-entry' ).find( '.sidebar-data' ).trigger( 'click' );
        selected.trigger( 'click' );
    }
} );