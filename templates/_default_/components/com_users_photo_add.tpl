<h1 class="con_heading">{$LANG.ADD_PHOTOS}</h1>
{if $total_no_pub}
<p class="usr_photos_add_limit">{$LANG.NO_PUBLISHED_PHOTO}: <a href="/users/{$user_login}/photos/submit">{$total_no_pub|spellcount:$LANG.PHOTO:$LANG.PHOTO2:$LANG.PHOTO10}</a></p>
{/if}
{if !$stop_photo}
	{if $uload_type == 'multi'}
{add_js file='includes/multiupload/multiupload.js'}
{add_css file='includes/multiupload/multiupload.css'}

<script type="text/javascript">
    var uploadedCount = 0;

    window.onload = function() {
        new MultiUpload({
            uploadUrl: '/components/users/ajax/upload_photo.php',
            params: { "sess_id" : "{$sess_id}" },
            accept: '.jpg,.jpeg,.png,.gif',
            maxFiles: {if $max_limit}{$max_files}{else}100{/if},
            maxBytes: 20971520,
            fileField: 'Filedata',
            buttonLabel: '{$LANG.UPLOAD}',
            buttonTarget: 'spanButtonPlaceHolder',
            progressTarget: 'fsUploadProgress',
            cancelBtn: 'btnCancel',
            langError: '{$LANG.UPLOAD_ERROR}',
            langTooLarge: '{$LANG.ERR_LARGE_FILE}',
            onQueueComplete: function(uploaded) {
                if (uploaded > 0){
                    uploadedCount += uploaded;
                    document.getElementById('divStatus').style.display = 'block';
                    document.getElementById('continue').style.display = 'inline';
                    document.getElementById('files_count').innerHTML = uploadedCount;
                }
            }
        });
    };
</script>

<form id="usr_photos_upload_form" action="" method="post" enctype="multipart/form-data">

    {if $max_limit}
    <p class="usr_photos_add_limit">{$LANG.YOU_CAN_UPLOAD} <strong>{$max_files}</strong> {$LANG.PHOTO_SHORT}</p>
    {/if}

        <div class="fieldset flash" id="fsUploadProgress" style="display:none">
            <span class="legend">{$LANG.UPLOAD_QUEUE}</span>
        </div>

        <div>
            <span id="spanButtonPlaceHolder"></span>
            <input id="btnCancel" type="button" value="{$LANG.CANCEL}" onclick="swfu.cancelQueue();" disabled="disabled" style="margin-left: 2px; font-size: 8pt; height: 36px;" />
        </div>

        <div id="divStatus" style="display:none">
            {$LANG.UPLOADED} <span id="files_count"><strong>0</strong></span> {$LANG.PHOTO_SHORT}.
            <a href="/users/{$user_login}/photos/submit" id="continue">{$LANG.CONTINUE}</a>
        </div>

</form>
        <p class="usr_photos_add_st">{$LANG.TEXT_TO_NO_FLASH} <a href="/users/addphotosingle.html">{$LANG.PHOTO_ST_UPLOAD}.</a></p>
    {elseif $uload_type == 'single'}
        {if $max_limit}
         <p class="usr_photos_add_limit">{$LANG.YOU_CAN_UPLOAD} <strong>{$max_files}</strong> {$LANG.PHOTO_SHORT}</p>
        {/if}

        <form id="usr_photos_upload_form" enctype="multipart/form-data" action="/users/photos/upload" method="POST">
            <p>{$LANG.SELECT_UPLOAD_FILE}: </p>
            <input name="Filedata" type="file" id="picture" size="30" />
            <input name="upload" type="hidden" value="1"/>
            <div style="margin-top:5px">
                <strong>{$LANG.TYPE_FILE}:</strong> gif, jpg, jpeg, png
            </div>

            <p>
                <input type="submit" value="{$LANG.UPLOAD}">
                <input type="button" onclick="window.history.go(-1);" value="{$LANG.CANCEL}"/>
            </p>
        </form>
		<p class="usr_photos_add_st">{$LANG.TEXT_TO_TO_FLASH} <a href="/users/addphoto.html">{$LANG.PHOTO_FL_UPLOAD}.</a></p>
    {/if}
{else}
<p class="usr_photos_add_limit">{$LANG.FOR_ADD_PHOTO_TEXT}</p>
{/if}