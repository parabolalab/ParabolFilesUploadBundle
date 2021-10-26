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

function onLoadFilemanager()
{
  $(this).contents().on('click','.select',function () {
      var path = $(this).attr('data-path')
      // $('#path').val(path);
      // $('#image').attr('src', path)  

      fetch(path)
        .then(function(response){ return response.blob() })
        .then(function(blob){ 
            console.log($(fileManagerWidget).closest('.fileupload'))
            $(fileManagerWidget).closest('.fileupload').fileupload('add', { files: [ blob ] });
            $('#adminModal').modal('hide')
      })

  });
}

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


    if($('.form-model > form, .sonata-ba-form > form').length)
    {
      $('.form-model > form, .sonata-ba-form > form').submit(function(){
          for(var i in parabol_fileupload_sortableNewValues)
          {
            $('#' + i).val(JSON.stringify(parabol_fileupload_sortableNewValues[i]).replace(/\-fileupload/g, ''))
          }
      })  
    }

})


var fileManagerWidget = null;

function openFileManager(e)
{

    fileManagerWidget = e.currentTarget;

    let modal = $($(fileManagerWidget).data('modal'));
    if(modal.find('.modal-body #filemanager').length === 0) 
      modal.find('.modal-body').html('<style>#adminModal > .modal-dialog { width: 97vw;} </style></script><iframe id="filemanager" src="' + $(fileManagerWidget).data('path') + '" style="width: 100%; min-height: 500px; height: -webkit-calc(100vh - (200px)); height: -moz-calc(100vh - (200px)); height: calc(100vh - (200px));" frameborder="0"></iframe><script>$("#filemanager").on("load", onLoadFilemanager )</script>')

    modal.modal('show');
}


function initFileUpload(items)
{
  if(items.length)
  {  

      function renewSortableValues(key, id, $obj)
      {
        parabol_fileupload_sortableNewValues[key][id] = { length: 0, values: {} }

        $obj.children().each(function(index){

          parabol_fileupload_sortableNewValues[key][id].values[$(this).attr('data-id')] = index + 1; 
          $(this).attr('data-sort', index + 1)
          parabol_fileupload_sortableNewValues[key][id].length++;
        })

        $('#' + key).val(parabol_fileupload_sortableNewValues);

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

        $(this).find('.filesmanager-button').click(openFileManager)


        var keyIndex = $input.attr('id').replace(new RegExp('_' + $input.data('context') + '$'), '')

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
              formData: {class: $input.data('class'), multiple: $input.prop('multiple') ? 1 : 0, hash: $input.data('hash'), ref: $input.data('ref'), context: $input.data('context'), path: $input.data('path'), 'acceptedMimeTypes': $input.data('acceptmimetypes')}         
            })
        .on('fileuploadfinished', function (e, data) {
          if(!$('#' + id + '-files > li:last-child').hasClass('error'))
          {
            if(!$input.attr('multiple') && $('#' + id + '-files > li').length > 1) $('#' + id + '-files > li:not(:last-child)').remove();

            console.log('fileuploadfinished', $input.data('order'), $('#' + id + ' .files > li'))

            if($input.data('order') == 'desc') $('#' + id + ' .files > li:last-child').prependTo('#' + id + ' .files');
            
            updateFilesUpdatedAt(keyIndex)

            if(parabol_fileupload_sortableNewValues[keyIndex + '_filesOrder'][id].length) renewSortableValues(keyIndex + '_filesOrder', id, $('ul#' + id + '-files'))
                $('#' + id + ' .file-list .label:lt('+$('#' + id + ' .files > li').length+')').removeClass('hidden'); 

              }
            })
          .on('fileuploadprocessstart', function (e) {
            
            if(typeof files_browser_maxPerPage == 'number')
            {
                if($('#' + id + '-files > li').length + 1 > files_browser_maxPerPage) $('#' + id + '-files > li:' + ($input.data('order') == 'desc' ? 'last' : 'first') + '-child').hide();
            }

          })
        .on('fileuploaddestroyed', function (e, data) {
          updateFilesUpdatedAt(keyIndex)
          renewSortableValues(keyIndex + '_filesOrder', id, $('ul#' + id + '-files'))
        })
        ;



        if($input.data('class'))
        {
          
          var params = {params: {class: $input.data('class'), ref: $input.data('ref'),  hash: $input.data('hash') , context: $input.data('context') }, type: $input.data('type'), order: $input.data('order') }

          if($input.data('page')) params.page = $input.data('page');
          console.log(typeof files_browser_maxPerPage)
          if(typeof files_browser_maxPerPage === 'number') params.maxPerPage = files_browser_maxPerPage;

          $.getJSON(
            sf_env+'/admin/_uploader/get', 
            params,
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

                  var sortableOptions = {
                    placeholder: '<li class="placeholder template-download btn btn-default"></li>'
                  }

                  if($.fn.sortable( "option", "cursor") !== 'undefined')
                  {
                    //new version with jquery-ui sortable
                    sortableOptions['update'] =  function(event, ui) {

                        var $item = $(event.target.closest('tr'))
                        $item.removeClass("dragged").removeAttr("style")
                        $("body").removeClass("dragging")
                        renewSortableValues(keyIndex + '_filesOrder', id, $(this))
                        updateFilesUpdatedAt(keyIndex)
                    }
                  }
                  else {

                    //old version with jquery-sortable
                    sortableOptions['onDrop'] = function ($item, container, _super, event) {
                      $item.removeClass("dragged").removeAttr("style")
                      $("body").removeClass("dragging")
                      renewSortableValues(keyIndex + '_filesOrder', id, $(container.el))
                      updateFilesUpdatedAt(keyIndex)

                    }
                  }

                    $('#' + id + ' ul.files').sortable(sortableOptions)

                    $('#' + id + ' [role=append]').html(files.append)
              });
          }
        })
    }
}

