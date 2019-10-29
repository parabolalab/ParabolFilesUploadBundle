var parabol_fileupload_sortableNewValues = {};
var parabol_fileupload_hash = null;

if(typeof sf_env === 'undefined' ) var sf_env = '';

function hashCode(value) {

  var hash = 0, i, chr;
  if (value.length === 0) return hash;
  for (i = 0; i < value.length; i++) {
    chr   = value.charCodeAt(i);
    hash  = ((hash << 5) - hash) + chr;
    hash |= 0; // Convert to 32bit integer
  }
  return Math.abs(hash).toString(16) + Math.abs(hash * 1234567).toString(16);
};

$(document).ready(function () {
       
    $cookieshash = document.cookie.match(/parabol_fileupload_hash=([^;]+)/)
    
    if($cookieshash === null)
    {
      parabol_fileupload_hash = hashCode(Math.random().toString())
      document.cookie = 'parabol_fileupload_hash=' + parabol_fileupload_hash;
    }
    else {
      parabol_fileupload_hash = $cookieshash[1]
    }

    
    if($('.form-model-type-symfonycomponentformextensioncoretypecollectiontype').length)
    {
        document.addEventListener('collection_add', function (e) { 
            initFileUpload($(e.target).find('.fileupload'));
        }, true);
    }
    
    initFileUpload($('.fileupload'))


    if($('.form-model > form').length)
    {
      $('.form-model > form').submit(function(){
          for(var i in parabol_fileupload_sortableNewValues)
          {
            $('#' + i).val(JSON.stringify(parabol_fileupload_sortableNewValues[i]).replace(/\-fileupload/g, ''))
          }
      })  
    }

})




function initFileUpload(items)
{
  if(items.length)
  {  

      function renewSortableValues(key, id, $obj)
      {
        var max = $obj.children().length;
        
        parabol_fileupload_sortableNewValues[key][id] = { length: 0, values: {} }
        
        $obj.children().each(function(index){
          parabol_fileupload_sortableNewValues[key][id].values[$(this).attr('data-id')] = max - index; 
          $(this).attr('data-sort', max - index)
          parabol_fileupload_sortableNewValues[key][id].length++;
        })
      }

      function updateFilesUpdatedAt(prefix)
      {
        if($('input[id$='+prefix+'_filesUpdatedAt]').length) $('input[id$='+prefix+'_filesUpdatedAt]').val(moment().format('YYYY-MM-DD HH:mm:ss'))
      }
        

      items.each(function(){

        var id = $(this).attr('id');
        
        var files_error = false;
        $('.alert.alert-danger li').each(function(){
          if($(this).text().trim() == 'Files error') files_error = true;
        })
        if(files_error) $(this).find('.files-error').removeClass('hidden');

        // acceptFileTypes = 

        var $input = $(this).find('.fileupload-input')
        var keyIndex = $input.attr('id').replace('_' + $input.data('context'), '')

        if(typeof parabol_fileupload_sortableNewValues[keyIndex + '_filesOrder'] == 'undefined') parabol_fileupload_sortableNewValues[keyIndex + '_filesOrder'] = {}
        parabol_fileupload_sortableNewValues[keyIndex + '_filesOrder'][id] =  {'values': {}, length: 0}
        
        var acceptmimetypes = new RegExp('(\.|\/)(' + $input.data('acceptmimetypes') + ')$', 'i')
        
        
        if($('#'+keyIndex+'_filesHash').val() == '')
        { 
            var hash = parabol_fileupload_hash + hashCode($input.attr('id'))

            $input.data('hash', hash)
            $('#'+keyIndex+'_filesHash').val(hash)
        }
        else
        {
          $input.data('hash', $('#'+keyIndex+'_filesHash').val())
          
        }

        $(this).fileupload({
              dataType: 'json',
              autoUpload: true,
              // acceptFileTypes: acceptmimetypes,
              downloadTemplateId: $input.attr('id') + '-template-download',
              uploadTemplateId: $input.attr('id') + '-template-upload',
              disableImageResize: true,
              previewMaxWidth: 100,
              previewMaxHeight: 100,
              previewCrop: true,
              formData: {class: $input.data('class'), hash: $input.data('hash'), ref: $input.data('ref'), context: $input.data('context'), path: $input.data('path'), 'acceptedMimeTypes': $input.data('acceptmimetypes')}         
            })
        .on('fileuploadfinished', function (e, data) {
          if(!$('#' + id + '-files > li:last-child').hasClass('error'))
          {
            if(!$input.attr('multiple') && $('#' + id + '-files > li').length > 1) $('#' + id + '-files > li:not(:last-child)').remove();
            
            updateFilesUpdatedAt(keyIndex)

            if(parabol_fileupload_sortableNewValues[keyIndex + '_filesOrder'][id].length) renewSortableValues(keyIndex + '_filesOrder', id, $('ul#' + id + '-files'))
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
          updateFilesUpdatedAt(keyIndex)
          renewSortableValues(keyIndex + '_filesOrder', id, $('ul#' + id + '-files'))
        })
        ;
          

        if($input.data('class'))
        {
          
          $.getJSON(
            sf_env+'/admin/_uploader/get', 
            {params: {class: $input.data('class'), ref: $input.data('ref'),  hash: $input.data('hash') , context: $input.data('context') }, type: $input.data('type') },
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

                    renewSortableValues(keyIndex + '_filesOrder', id, $('#' + id + ' ul.files'))

           
                  var event = new Event('fileuploadrendered');
                  $input[0].dispatchEvent(event);

                    $('#' + id + ' ul.files').sortable({
                      placeholder: '<li class="placeholder template-download btn btn-default"></li>',
                      onDrop: function ($item, container, _super, event) {
                  $item.removeClass("dragged").removeAttr("style")
                  $("body").removeClass("dragging")
                  renewSortableValues(keyIndex + '_filesOrder', id, $(container.el))
                  updateFilesUpdatedAt(keyIndex)



                        // $.post(sf_env+'/_uploader/update-position', $item.data(), function(jdata){
                        //    if(jdata.result != 'success')
                        //    {
                        //      alert('error');
                        //    }
                        // });

                }
                    })
              });
          }
        })
    }
}

