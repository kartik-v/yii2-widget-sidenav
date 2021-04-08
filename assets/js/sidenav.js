/*!
 * @copyright Copyright &copy; Kartik Visweswaran, Krajee.com, 2013 - 2021
 * @package yii2-widgets
 * @version 1.0.1
 *
 * Side navigation menu bar styling for Bootstrap 3.x & 4.x
 * Built for Yii Framework 2.0
 * Author: Kartik Visweswaran
 * Year: 2013 - 2021
 * For more Yii related demos visit http://demos.krajee.com
 */
var kvSideNavInit = function (id, activeCss, addlCss) {
    $('#' + id + ' .kv-toggle').click(function (e) {
        var $el = $(this);
        e.preventDefault(); // cancel the event
        $el.children('.opened').toggle();
        $el.children('.closed').toggle();
        $el.parent().children('ul').toggle();
        $el.parent().toggleClass(activeCss);
        if ($el.hasClass('nav-link')) { // for bootstrap 4.x
            $el.toggleClass(activeCss);
        }
        if (addlCss) {
            $el.toggleClass(addlCss);
        }
        return false;
    });
};
