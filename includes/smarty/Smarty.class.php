<?php
/******************************************************************************/
//                                                                            //
//          InstantCMS: подключение Smarty 5 в стиле Smarty 4                 //
//                                                                            //
//  Файл оставлен под прежним именем (includes/smarty/Smarty.class.php),      //
//  чтобы не менять код CMS: он подключает Smarty 5 и создаёт глобальный      //
//  алиас класса Smarty (в Smarty 5 класс называется Smarty\Smarty).          //
/******************************************************************************/

require_once __DIR__ . '/libs/Smarty.class.php';

if (!class_exists('Smarty', false)) {
    class_alias('Smarty\\Smarty', 'Smarty');
}
