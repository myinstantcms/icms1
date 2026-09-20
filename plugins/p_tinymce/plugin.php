<?php
/******************************************************************************/
//                                                                            //
//                           InstantCMS v1.10.7                               //
//                        http://www.instantcms.ru/                           //
//                                                                            //
//                   written by InstantCMS Team, 2007-2016                    //
//                produced by InstantSoft, (www.instantsoft.ru)               //
//                                                                            //
//                        LICENSED BY GNU/GPL v2                              //
//                                                                            //
//      TinyMCE — свободный редактор (GPLv2+), замена CKEditor 4 (EOL).       //
/******************************************************************************/

class p_tinymce extends cmsPlugin {

    public $config = array(
        'iswatermark'            => 0,
        'photo_width'            => 600,
        'photo_height'           => 600,
        'allow_file_ext'         => '',
        'upload_file_for_groups' => array(2),
        'upload_for_groups'      => array(2)
    );

    public function __construct() {

        global $_LANG;

        $this->info = array(
            'plugin'      => 'p_tinymce',
            'title'       => 'TinyMCE',
            'description' => $_LANG['TM_DESCRIPTION'],
            'author'      => 'InstantCMS Team',
            'version'     => '8.9.1',
            'published'   => 1,
            'plugin_type' => 'wysiwyg'
        );

        $this->events = array(
            'INSERT_WYSIWYG'
        );

        parent::__construct();

    }

    public function execute($event = '', $item = array()) {

        $access = (cmsUser::getInstance()->is_admin) ? 'admin' : 'user';
        $width  = (is_numeric($item['width']) ? $item['width'].'px' : $item['width']);
        $height = (is_numeric($item['height']) ? (int)$item['height'] : (int)$item['height']);

        $plugins = $this->getPlugins($item['toolbar']);
        $toolbar = $this->getToolbar($item['toolbar'], $access);

        $image_upload = ($this->canUpload() && $this->inCore->component) ?
                '/plugins/p_tinymce/upload.php?type=image&component='.$this->inCore->component : '';

        $file_upload = ($this->canFileUpload() && !empty($this->config['allow_file_ext']) && $this->inCore->component) ?
                '/plugins/p_tinymce/upload.php?type=file&component='.$this->inCore->component : '';

        $lang = cmsConfig::getConfig('lang');

        ob_start(); ?>

        <textarea id="<?php echo $item['name']; ?>" name="<?php echo $item['name']; ?>" style="width: <?php echo $width; ?>; height: <?php echo $height; ?>px;"><?php echo htmlspecialchars((string)$item['text']); ?></textarea>
        <script type="text/javascript">
        (function(){
            function icmsTinyMceInit<?php echo $item['name']; ?>(){
                tinymce.init({
                    // TinyMCE требует явно подтвердить условия открытой лицензии (GPLv2+)
                    license_key: 'gpl',
                    target: document.getElementById("<?php echo $item['name']; ?>"),
                    language: <?php echo json_encode($lang); ?>,
                    height: <?php echo $height; ?>,
                    menubar: false,
                    branding: false,
                    promotion: false,
                    // компактная панель: классический скин, одна строка кнопок, без статус-строки
                    skin: 'tinymce-5',
                    toolbar_mode: 'sliding',
                    statusbar: false,
                    resize: true,
                    plugins: <?php echo json_encode($plugins); ?>,
                    toolbar: <?php echo json_encode($toolbar); ?>,
                    forced_root_block: 'p',
                    entity_encoding: 'raw',
                    convert_urls: false,
                    relative_urls: false,
                    remove_script_host: false,
                    // в CMS контент хранится как есть (в CKEditor было allowedContent: true)
                    verify_html: false,
                    valid_elements: '*[*]',
                    extended_valid_elements: 'iframe[src|width|height|frameborder|allow|allowfullscreen|style|class|loading|title],script[type|src|async],div[*],span[*]',
                    valid_children: '+body[style],+div[*],+span[*]',
                    content_css: '/plugins/p_tinymce/tinymce/contents.css',
                    <?php if ($image_upload) { ?>
                    images_upload_url: <?php echo json_encode($image_upload); ?>,
                    images_upload_credentials: true,
                    automatic_uploads: true,
                    image_uploadtab: true,
                    <?php } ?>
                    <?php if ($file_upload) { ?>
                    file_picker_types: 'file',
                    file_picker_callback: function(callback, value, meta){
                        var input = document.createElement('input');
                        input.setAttribute('type', 'file');
                        input.onchange = function(){
                            var data = new FormData();
                            data.append('upload', input.files[0]);
                            fetch(<?php echo json_encode($file_upload); ?>, { method: 'POST', body: data, credentials: 'same-origin' })
                                .then(function(r){ return r.json(); })
                                .then(function(json){
                                    if (json && json.location) { callback(json.location, { text: input.files[0].name }); }
                                    else if (json && json.error) { alert(json.error); }
                                });
                        };
                        input.click();
                    },
                    <?php } ?>
                    setup: function(editor){
                        editor.on('change input undo redo', function(){
                            editor.save();
                        });
                    }
                });
            }

            // редактор подключается один раз на страницу, инициализации полей
            // складываются в очередь и выполняются после загрузки скрипта
            window.__icmsTmceQueue = window.__icmsTmceQueue || [];

            function icmsTmceReady(fn){
                if (typeof tinymce !== 'undefined' && tinymce.init) { fn(); }
                else { window.__icmsTmceQueue.push(fn); }
            }

            if (!window.__icmsTmceCss) {
                window.__icmsTmceCss = true;
                var css = document.createElement('link');
                css.rel = 'stylesheet';
                css.href = '/plugins/p_tinymce/tinymce/icms-compact.css';
                document.head.appendChild(css);
            }

            if (typeof tinymce === 'undefined' && !window.__icmsTmceLoading) {
                window.__icmsTmceLoading = true;
                var script = document.createElement('script');
                script.type = 'text/javascript';
                script.src  = '/plugins/p_tinymce/tinymce/tinymce.min.js?v=8.9.1';
                script.onload = function(){
                    var queue = window.__icmsTmceQueue;
                    window.__icmsTmceQueue = [];
                    for (var i = 0; i < queue.length; i++) { queue[i](); }
                };
                document.head.appendChild(script);
            }

            icmsTmceReady(icmsTinyMceInit<?php echo $item['name']; ?>);
        })();
        </script>

        <?php return ob_get_clean();

    }

    /**
     * Набор подключаемых модулей редактора
     */
    private function getPlugins($toolbar = 'full') {

        return 'advlist anchor autolink charmap code fullscreen help image insertdatetime link lists media preview searchreplace table visualblocks wordcount';

    }

    /**
     * Панель инструментов: полная для администраторов, короткая для остальных
     */
    private function getToolbar($toolbar = 'full', $access = 'user') {

        if ($access !== 'admin' && $toolbar !== 'full') {
            return 'undo redo | bold italic underline | bullist numlist | link image | removeformat code';
        }

        return 'undo redo | formatselect | bold italic underline strikethrough | forecolor backcolor | '
             . 'alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | '
             . 'link image media table | blockquote hr charmap insertdatetime | removeformat | code fullscreen preview';
    }

    public function canUpload(){
        return $this->inGroup((array)$this->config['upload_for_groups']);
    }

    public function canFileUpload(){
        return $this->inGroup((array)$this->config['upload_file_for_groups']);
    }

    private function inGroup($groups){

        if (!$groups) { return false; }

        $inUser = cmsUser::getInstance();

        return isset($inUser->group_id) && in_array($inUser->group_id, $groups);
    }

}
