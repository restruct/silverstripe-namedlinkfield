jQuery.entwine("dependentdropdown", function ($) {

    // FIX: remove '[]' part from load-url for namedlinkfield-dependentdropdowns,
    // as such URLs end up at form instead of the field
    $(".LinkFormFieldPageAnchor :input.dependent-dropdown").entwine({

        onadd: function () {
            // fix data-link
            var loadurl = this.data('link').split('[')[0] + this.data('link').split(']')[1];
            this.data('link',loadurl);

            // fix double empty value
            this.find('option:first-child').remove();

            // call less specific entwine functionality
            this._super();
        }

    });

});


// jQuery.entwine('ss', function($){
//
// 	$('.LinkFormField .TreeDropdownField.filetree').entwine({
//
// 		// Fix TreeDropdown to allow multiple different data-urls (Page & File)
//
// 		loadTree: function(params, callback) {
// 			// // TreeDropdown seems quite stubborn to change the data-url to /tree over and over.
// 			// // So we change it back over and over...
// 			// this.data('urlTree',this.attr('data-url-tree'));
//             //
// 			// this._super(params, callback);
// 		},
//
// 		// onadd: function() {
// 		// 	// fix data url for treedropdown on File besides Page (.filetree)
//          //    let treedata = this.data('schema');
//          //    console.log(treedata);
//          //    if(treedata.data.urlTree) {
//          //        treedata.data.urlTree = treedata.data.urlTree.replace('[FileID]', '').replace('/tree', '/treefile');
//          //        console.log(treedata.data.urlTree);
//          //        this.data('schema', treedata);
//          //    }
// 		// 	// var datalink = this.attr('data-url-tree');
// 		// 	// // because we keep resetting in loadTree, we need to remove the LAST '[x]' part manually
// 		// 	// var datalink = datalink.split('[');
// 		// 	// // remove & capture last part
// 		// 	// var datalink_lastpart = datalink.pop();
// 		// 	// // remove first val of last part
// 		// 	// datalink_lastpart = datalink_lastpart.split(']');
// 		// 	// datalink_lastpart.shift();
// 		// 	// // and rebuild
// 		// 	// newdatalink = datalink.join('[') + datalink_lastpart.join(']');
// 		// 	// //if(this.hasClass('filetree')) {
// 		// 	// 	this.attr('data-url-tree', newdatalink + 'file'); // 'treefile' is routed differently from Named LF PHP
// 		// 	// //} else {
// 		// 	// //	this.attr('data-url-tree', datalink);
// 		// 	// //}
//         //
// 		// 	this._super();
// 		// }
//
// 	});
//
// });


(function($){

    function showLinkInput() {
        var mode = $(this).find('.LinkFormFieldLinkmode select').val();

        // 0 or >0 = something set, "" = 'None/Custom'
//		$('.LinkFormFieldCustomURL',this).css('display',
//			( $('.LinkFormFieldPageID input',this).val()=="" ? 'block' : 'none') );
        // Custom URL
        $('.LinkFormFieldCustomURL',this).css('display',
            ( (mode=="URL" || mode=="Email") ? 'block' : 'none') );
        // Shortcode
        $('.LinkFormFieldShortcode',this).css('display',
            ( mode=="Shortcode" ? 'block' : 'none') );
        // Page (Internal URL)
        $('.LinkFormFieldPageID',this).css('display',
            ( mode=="Page" ? 'inline-block' : 'none') );
        // Page Anchor
        $('.LinkFormFieldPageAnchor',this).css('display',
            ( mode=="Page" ? 'inline-block' : 'none') );
        // File
        $('.LinkFormFieldFileID',this).css('display',
            ( mode=="File" ? 'block' : 'none') );
    }

    $('.LinkFormField').entwine({
        onadd: function(){
            var $this = this;

            // Add listener
            $this.find('.LinkFormFieldLinkmode select').on('change', function(){
                showLinkInput.call($this);
            });

            // Initial setup
            showLinkInput.call($this);

            // Fix treedropdown links to be routed via the namedlinkformfield url handlers
            $this.find('.TreeDropdownField').each(function(){
                var treedata = $(this).data('schema');
                if(treedata.data.urlTree) {
                    treedata.data.urlTree = treedata.data.urlTree.replace('[PageID]', '');
                    if($(this).hasClass('filetree')) {
                        treedata.data.urlTree = treedata.data.urlTree.replace('[FileID]', '');
                        //treedata.data.urlTree = treedata.data.urlTree.replace('/tree', '/treefile');
                    }
                    $(this).data('schema', treedata);
                }
            });
        }
    });


})(jQuery);
