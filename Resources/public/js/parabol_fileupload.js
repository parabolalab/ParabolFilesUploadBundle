

$(document).ready(function () {
       
       if($('.fileupload').length)
	   {	

	   		var sortableNewValues = {};

			function renewSortableValues(id, $obj)
			{
				var max = $obj.children().length;
				sortableNewValues[id] = { length: 0, values: {} }
				
				$obj.children().each(function(index){
					sortableNewValues[id].values[$(this).attr('data-id')] = max - index; 
					$(this).attr('data-sort', max - index)
					sortableNewValues[id].length++;
				})
			}

			function updateFilesUpdatedAt()
			{
				if($('input[id$=_filesUpdatedAt]').length) $('input[id$=_filesUpdatedAt]').val(moment().format('YYYY-MM-DD HH:mm:ss'))
			}

	   		if($('input[id$=filesOrder]').length)
	   		{
	   			$('.fileupload').first().closest('form').submit(function(){
	   				 $('input[id$=filesOrder]').val(JSON.stringify(sortableNewValues).replace(/\-fileupload/g, ''));
	   			})	
	   		}
	   		

			$('.fileupload').each(function(){

				var id = $(this).attr('id');
				var filecontext = id.replace('-fileupload', '');
				sortableNewValues[id] =  {'values': {}, length: 0}
				var files_error = false;
				$('.alert.alert-danger li').each(function(){
					if($(this).text().trim() == 'Files error') files_error = true;
				})
				if(files_error) $(this).find('.files-error').removeClass('hidden');

				// acceptFileTypes = 

				var $input = $(this).find('.fileupload-input')

				
				var acceptmimetypes = new RegExp('(\.|\/)(' + $input.data('acceptmimetypes') + ')$', 'i')
				
				$(this).fileupload({
			       	dataType: 'json',
			       	autoUpload: true,
			        // acceptFileTypes: acceptmimetypes,
			        downloadTemplateId: filecontext + '-template-download',
			        uploadTemplateId: filecontext + '-template-upload',
			        disableImageResize: true,
			        previewMaxWidth: 100,
			        previewMaxHeight: 100,
			        previewCrop: true,
			        formData: {class: $input.data('class'), ref: $input.data('ref'), context: $input.data('context'), 'acceptedMimeTypes': $input.data('acceptmimetypes')}	        
		        })
				.on('fileuploadfinished', function (e, data) {
					if(!$('#' + id + '-files > li:last-child').hasClass('error'))
					{
						if(!$input.attr('multiple') && $('#' + id + '-files > li').length > 1) $('#' + id + '-files > li:not(:last-child)').remove();
						
						updateFilesUpdatedAt()

						if(sortableNewValues[id].length) renewSortableValues(id, $('ul#' + id + '-files'))
			   	    	$('#' + id + ' .file-list .label:lt('+$('#' + id + ' .files > li').length+')').removeClass('hidden'); 

			   	   	}
		   	    })
			    .on('fileuploadprocessstart', function (e) {
					if(typeof(parabol_file_browser_maxPerPage) == 'integer')
			    	{
			    			if($('#' + id + '-files > li').length > parabol_file_browser_maxPerPage) $('#' + id + '-files > li:' + ($input.data('order') == 'desc' ? 'last' : 'first') + '-child').hide();
			    	}
			    	if($input.data('order') == 'desc') $('#' + id + '-files > div:last-child').prependTo('#' + id + '-files');
			    })
				.on('fileuploaddestroyed', function (e, data) {
					updateFilesUpdatedAt()
					renewSortableValues(id, $('ul#' + id + '-files'))
				})
				;
			    

				if($input.data('class'))
				{
					
					$.getJSON(
						sf_env+'/admin/_uploader/get', 
						{params: {class: $input.data('class'), ref: $input.data('ref'), context: $input.data('context') }, type: $input.data('type') },
						function (files) {

							$('#' + id + ' .file-list .label').addClass('hidden');
							$('#' + id + ' .file-list .label:lt('+files.length+')').removeClass('hidden'); 

							var fu = $('#' + id).data('blueimp-fileupload'),
				            template;
				            // fu._adjustMaxNumberOfFiles(-files.length);
				            template = fu._renderDownload(files)
				              .appendTo($('#' + id).find('.files'));
				            // Force reflow:
				            fu._reflow = fu._transition && template.length &&
				              template[0].offsetWidth;
				            template.addClass('in');
				            $('#loading').remove();

				            renewSortableValues(id, $('#' + id + ' ul.files'))

				            $('#' + id + ' ul.files').sortable({
				            	placeholder: '<li class="placeholder template-download btn btn-default"></li>',
				            	onDrop: function ($item, container, _super, event) {
								  $item.removeClass("dragged").removeAttr("style")
								  $("body").removeClass("dragging")
								  renewSortableValues(id, $(container.el))
								  updateFilesUpdatedAt()



				            	  // $.post(sf_env+'/_uploader/update-position', $item.data(), function(jdata){
				            	  // 		if(jdata.result != 'success')
				            	  // 		{
				            	  // 			alert('error');
				            	  // 		}
				            	  // });

								}
				            })
			        });
			    }
		    })
		}
        

})

