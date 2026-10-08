// jQuery.entwine("dependentdropdown", function ($) {
// // jQuery.entwine('ss', ($) => {
//
//     // FIX: remove '[]' part from load-url for namedlinkfield-dependentdropdowns,
//     // as such URLs end up at form instead of the field
//     $(".LinkFormFieldPageAnchor :input.dependent-dropdown").entwine({
//
//         onadd: function () {
//             var loadurl = this.data('link');
//             // fix data-link
//             loadurl = loadurl.split('[')[0] + loadurl.split(']')[1];
//             this.data('link',loadurl);
//
//             // fix double empty value --> ? Some SS3 stuff probably
// //            this.find('option:first-child').remove();
//
//             //
//             // Because of this react crap we cannot listen for native DOM events anymore... workaround...
//             // Copied & adapted ONADD from dependentdropdownfield.js in order to make things work with the react rendered input of
// 			var drop = this;
// 			var depends = $(":input[name=" + drop.data('depends').replace(/[#;&,.+*~':"!^$[\]()=>|\/]/g, "\\$&") + "]");
// 			this.parents('.field:first').addClass('dropdown');
// //			depends.change(function () {
// 			depends.parent().on('change', function (event) {
// //				if (!this.value) {
// 				if (!event.target.value) {
// 					drop.disable(drop.data('unselected'));
// 				} else {
// 					drop.disable("Loading...");
// 					$.get(drop.data('link'), {
// //						val: this.value
// 						val: event.target.value
// 					},
// 					function (data) {
// 						drop.enable();
// 						if (drop.data('empty') || drop.data('empty') === "") {
// 							drop.append($("<option />").val("").text(drop.data('empty')));
// 						}
// 						$.each(data, function () {
// 							drop.append($("<option />").val(this.k).text(this.v));
// 						});
// 						drop.trigger("liszt:updated").trigger("chosen:updated").trigger("change");
// 					});
// 				}
// 			});
// 			if (!depends.val()) {
// 				drop.disable(drop.data('unselected'));
// 			}
// 			//
// 			// End of workaround
// 			//
//
//             // call less specific entwine functionality --> dont call this anymore because we've replaced it with the updated onadd above...
// //            this._super();
//         }
//
//     });
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
                // The call() method calls a function with a given this value and arguments provided individually
                showLinkInput.call($this);
            });

            // Initial setup (The call() method calls a function with a given this value and arguments provided individually)
            showLinkInput.call($this);

            // // Fix treedropdown links to be routed via the namedlinkformfield url handlers -> not necessary anymore since we're now extending FieldGroup
            // $this.find('.TreeDropdownField').each(function(){
            //     var treedata = $(this).data('schema');
            //     if(treedata.data.urlTree) {
            //         // treedata.data.urlTree = treedata.data.urlTree.replace('PageID', '');
            //         if($(this).hasClass('filetree')) {
            //             // treedata.data.urlTree = treedata.data.urlTree.replace('FileID', '');
            //             treedata.data.urlTree = treedata.data.urlTree.replace('/tree', '/treefile');
            //         }
            //         $(this).data('schema', treedata);
            //     }
            // });
        }
    });

    // Reload the page-anchor dropdown when a page is picked in the tree (#43).
    // DependentDropdownField's own script binds its "change" handler directly to
    // :input[name=<Name>PageID] when the anchor dropdown matches. At that moment that input is the
    // server-rendered placeholder inside the TreeDropdownField holder; the admin's TreeDropdownField
    // entwine then renders its React component into that holder (createRoot), which REPLACES the
    // placeholder. The handler stays on the detached element, so picking a page never reaches it.
    // The admin does fire a jQuery "change" on the new hidden input after each pick, and entwine
    // handles events by delegation, so a rule on the live input sees it whenever it was rendered.
    // (The commented-out workaround at the top of this file did the same via depends.parent().)
    $('.LinkFormField .LinkFormFieldPageID input[type=hidden][name$="PageID"]').entwine({
        onchange: function () {
            var pageID = this.val();
            var drop = this.closest('.LinkFormField').find('.LinkFormFieldPageAnchor :input.dependent-dropdown');
            if (!drop.length) {
                return;
            }

            // Same contract as dependentdropdownfield.js: no value = disabled with the 'unselected'
            // text, otherwise ask the field's load action (?val=<page ID>) for [{k, v}, ...]
            if (!pageID) {
                drop.empty().append($('<option />').val('').text(drop.data('unselected')))
                    .attr('disabled', 'disabled');
                return;
            }
            drop.empty().append($('<option />').val('').text('Loading...')).attr('disabled', 'disabled');
            $.get(drop.data('link'), { val: pageID }, function (data) {
                drop.empty().removeAttr('disabled');
                if (drop.data('empty') || drop.data('empty') === '') {
                    drop.append($('<option />').val('').text(drop.data('empty')));
                }
                $.each(data, function () {
                    drop.append($('<option />').val(this.k).text(this.v));
                });
                // Let chosen (if applied) and change listeners pick up the new options
                drop.trigger('liszt:updated').trigger('chosen:updated').trigger('change');
            });
        }
    });

})(jQuery);
