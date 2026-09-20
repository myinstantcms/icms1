<h3 style="border-bottom: solid 1px gray">
	<strong>{$LANG.STEP_2}</strong>: {$LANG.FILE_UPLOAD}
</h3>
{if !$stop_photo}
{if $uload_type == 'multi'}
{add_js file='includes/multiupload/multiupload.js'}
{add_css file='includes/multiupload/multiupload.css'}

<script type="text/javascript">
    window.onload = function() {
        new MultiUpload({
            uploadUrl: '{$upload_url}',
            params: { "sess_id" : "{$sess_id}", "album_id" : {$album.id} },
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
                    window.location.href = '{$upload_complete_url}';
                }
            }
        });
    };
</script>

<form id="usr_photos_upload_form" action="" method="post" enctype="multipart/form-data">

    {if $max_limit}
    <p class="usr_photos_add_limit">{$LANG.YOU_CAN_UPLOAD} <strong>{$max_files}</strong> {$LANG.PHOTO}</p>
    {/if}
        <div class="fieldset flash" id="fsUploadProgress" style="display:none">
            <span class="legend">{$LANG.UPLOAD_QUEUE}</span>
        </div>
        <div>
            <span id="spanButtonPlaceHolder"></span>
            <input id="btnCancel" type="button" value="{$LANG.CANCEL}" onclick="swfu.cancelQueue();" disabled="disabled" style="margin-left: 2px; font-size: 8pt; height: 36px;" />
        </div>

</form>

{elseif $uload_type == 'single'}
        {if $max_limit}
         <p class="usr_photos_add_limit">{$LANG.YOU_CAN_UPLOAD} <strong>{$max_files}</strong> {$LANG.PHOTO}</p>
        {/if}

        <form enctype="multipart/form-data" action="{$upload_url}" method="POST">

            <p>{$LANG.SELECT_FILE_TO_UPLOAD}: </p>
                    <input name="Filedata" type="file" id="picture" size="30" />
                    <input name="upload" type="hidden" value="1"/>
                    <input name="album_id" type="hidden" value="{$album.id}"/>
                    <input name="sess_id" type="hidden" value="{$sess_id}"/>

            <div style="margin:5px 0">
                <strong>{$LANG.ALLOW_FILE_TYPE}:</strong> gif, jpg, jpeg, png
            </div>

            <p>
                <input type="submit" value="{$LANG.LOAD}">
                <input type="button" onclick="window.history.go(-1);" value="{$LANG.CANCEL}"/>
            </p>
        </form>
{/if}
{else}
<p class="usr_photos_add_limit">{$LANG.MAX_UPLOAD_IN_DAY}</p>
{/if}