(function($){
	
	function showLinkInput() {
		var mode = $(this).find('.LinkFormFieldLinkmode select').val();
		
		// 0 or >0 = something set, "" = 'None/Custom'
//		$('.LinkFormFieldCustomURL',this).css('display', 
//			( $('.LinkFormFieldPageID input',this).val()=="" ? 'block' : 'none') );
		// Custom URL
		$('.LinkFormFieldCustomURL',this).css('display', 
			( mode=="external" ? 'inline' : 'none') );
		// Internal URL
		$('.LinkFormFieldPageID',this).css('display', 
			( mode=="internal" ? 'inline' : 'none') );
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
			
			// Fix bug in SS
			var $tree = $this.find('.TreeDropdownField');
			if ($tree.data('urlTree')) {
				$tree.data('urlTree', $tree.data('urlTree').replace('[PageID]',''));
			}
		}
	});
	
	
})(jQuery);