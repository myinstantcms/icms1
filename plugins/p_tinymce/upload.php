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
//   Загрузка файлов и изображений из редактора TinyMCE.                      //
//   Ответ — JSON вида {"location": "<url>"} либо {"error": "<текст>"}.       //
/******************************************************************************/

ini_set('display_errors', 0);
error_reporting(E_ALL);

session_start();

define("VALID_CMS", 1);
define('PATH', $_SERVER['DOCUMENT_ROOT']);

include(PATH.'/core/cms.php');

$inCore = cmsCore::getInstance();

cmsCore::loadClass('page');
cmsCore::loadClass('user');
cmsCore::loadClass('upload_photo');

$inUser = cmsUser::getInstance();

header('Content-Type: application/json; charset=utf-8');

function tmceResponse($path = '', $error = ''){

    if ($error) {
        echo json_encode(array('error' => $error));
    } else {
        echo json_encode(array('location' => $path));
    }

    cmsCore::halt();
}

if (!$inUser->update()) { tmceResponse('', 'ACCESS DENIED'); }
if (!$inUser->id) { tmceResponse('', 'ACCESS DENIED'); }

// Получаем компонент, с которого идет загрузка
$component = cmsCore::request('component', 'str', 'content');
if(!$inCore->isComponentInstalled($component)) { tmceResponse('', 'UNKNOWN COMPONENT'); }

// Что загружаем: изображения или другие файлы
$type = cmsCore::request('type', 'str', '');

// объект плагина
$plugin = $inCore->loadPlugin('p_tinymce');

global $_LANG;

$http_path = '';
$error     = $_LANG['TM_UPLOAD_ERROR'];

// грузим изображения
if($type === 'image'){

    $inUploadPhoto = cmsUploadPhoto::getInstance();

    if(!$plugin->canUpload()){ tmceResponse('', $_LANG['TM_UPLOAD_DENIED']); }

    $inUploadPhoto->upload_dir    = PATH.'/upload/';
    $inUploadPhoto->medium_size_w = $plugin->config['photo_width'];
    $inUploadPhoto->medium_size_h = $plugin->config['photo_height'];
    $inUploadPhoto->thumbsqr      = false;
    $inUploadPhoto->is_watermark  = $plugin->config['iswatermark'];
    $inUploadPhoto->only_medium   = true;
    $inUploadPhoto->dir_medium    = 'wysiwyg/';
    $inUploadPhoto->input_name    = 'upload';

    $file = $inUploadPhoto->uploadPhoto();

    if (!empty($file['filename'])) {
        $http_path = '/upload/wysiwyg/'.$file['filename'];
        $error     = '';
    }

}

// грузим другие файлы
if($type === 'file'){

    if(empty($plugin->config['allow_file_ext']) || !$plugin->canFileUpload()){ tmceResponse('', $_LANG['TM_UPLOAD_DENIED']); }

    $allow_ext = explode(',', $plugin->config['allow_file_ext']);
    $allow_ext = array_map(function($val){ return trim((string)$val); }, $allow_ext);

    if (!empty($_FILES['upload']['name'])){

        $input_name = preg_replace('/[^a-zA-Zа-яёЁА-Я0-9\.\-_ ]/ui', '', basename(strval($_FILES['upload']['name'])));
        $ext        = mb_strtolower(pathinfo($input_name, PATHINFO_EXTENSION));

        if ($ext && in_array($ext, $allow_ext, true)) {

            $upload_dir = PATH.'/upload/wysiwyg/';
            if (!is_dir($upload_dir)) { @mkdir($upload_dir, 0777, true); }

            $uploadpath = $upload_dir.md5(microtime().uniqid()).'.'.$ext;
            $source     = $_FILES['upload']['tmp_name'];
            $errorCode  = $_FILES['upload']['error'];

            if (cmsCore::moveUploadedFile($source, $uploadpath, $errorCode)) {
                $http_path = str_replace(PATH, '', $uploadpath);
                $error     = '';
            }

        } else {
            $error = $_LANG['TM_UPLOAD_EXT_ERROR'];
        }

    }

}

tmceResponse($http_path, $error);
