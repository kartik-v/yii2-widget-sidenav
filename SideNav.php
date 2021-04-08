<?php

/**
 * @copyright Copyright &copy; Kartik Visweswaran, Krajee.com, 2013 - 2021
 * @package yii2-widgets
 * @subpackage yii2-widget-sidenav
 * @version 1.0.1
 */

namespace kartik\sidenav;

use kartik\base\BootstrapInterface;
use kartik\base\BootstrapTrait;
use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\helpers\ArrayHelper;
use yii\widgets\Menu;

/**
 * A custom extended side navigation menu extending Yii Menu
 *
 * For example:
 *
 * ```php
 * echo SideNav::widget([
 *     'items' => [
 *         [
 *             'url' => ['/site/index'],
 *             'label' => 'Home',
 *             'icon' => 'home'
 *         ],
 *         [
 *             'url' => ['/site/about'],
 *             'label' => 'About',
 *             'icon' => 'info-sign',
 *             'items' => [
 *                  ['url' => '#', 'label' => 'Item 1'],
 *                  ['url' => '#', 'label' => 'Item 2'],
 *             ],
 *         ],
 *     ],
 * ]);
 * ```
 *
 * @author Kartik Visweswaran <kartikv2@gmail.com>
 */
class SideNav extends Menu implements BootstrapInterface
{
    use BootstrapTrait;

    /**
     * Panel contextual states
     */
    const TYPE_DEFAULT = 'default';
    const TYPE_SECONDARY = 'secondary';
    const TYPE_PRIMARY = 'primary';
    const TYPE_INFO = 'info';
    const TYPE_SUCCESS = 'success';
    const TYPE_DANGER = 'danger';
    const TYPE_WARNING = 'warning';

    /**
     * @var string the menu container style. This is one of the bootstrap panel
     * contextual state classes. Defaults to `default`.
     * @see http://getbootstrap.com/components/#panels
     */
    public $type = self::TYPE_DEFAULT;

    /**
     * @var string prefix for the icon in [[items]]. This string will be prepended
     * before the icon name to get the icon CSS class. This defaults to `glyphicon glyphicon-`
     * for usage with glyphicons available with Bootstrap.
     */
    public $iconPrefix;

    /**
     * @var array string/boolean the sidenav heading. This is not HTML encoded
     * When set to false or null, no heading container will be displayed.
     */
    public $heading = false;

    /**
     * @var string additional CSS class to be appended to each navigation item link. For a submenu this class will be
     * automatically removed when opened and added back when closed. You can add multiple class separated by spaces.
     * This defaults to `text-secondary` for bsVersion `4.x` and empty string for bsVersion `3.x`.
     *
     * Note: If you need to add a permanent CSS class to a link - do not use this and instead directly edit the
     * [[linkTemplate]] property.
     */
    public $addlCssClass;

    /**
     * @var array options for the sidenav heading
     */
    public $headingOptions = [];

    /**
     * @var array options for the sidenav container
     */
    public $containerOptions = [];

    /**
     * @var string indicator for a menu sub-item
     */
    public $indItem = '&raquo; ';

    /**
     * @var string indicator for a opened sub-menu
     */
    public $indMenuOpen;

    /**
     * @var string indicator for a closed sub-menu
     */
    public $indMenuClose;

    /**
     * @var array list of sidenav menu items. Each menu item should be an array of the following structure:
     *
     * - label: string, optional, specifies the menu item label. When [[encodeLabels]] is true, the label
     *   will be HTML-encoded. If the label is not specified, an empty string will be used.
     * - icon: string, optional, specifies the glyphicon name to be placed before label.
     * - url: string or array, optional, specifies the URL of the menu item. It will be processed by [[Url::to]].
     *   When this is set, the actual menu item content will be generated using [[linkTemplate]];
     * - visible: boolean, optional, whether this menu item is visible. Defaults to true.
     * - items: array, optional, specifies the sub-menu items. Its format is the same as the parent items.
     * - active: boolean, optional, whether this menu item is in active state (currently selected).
     *   If a menu item is active, its CSS class will be appended with [[activeCssClass]].
     *   If this option is not set, the menu item will be set active automatically when the current request
     *   is triggered by [[url]]. For more details, please refer to [[isItemActive()]].
     * - template: string, optional, the template used to render the content of this menu item.
     *   The token `{url}` will be replaced by the URL associated with this menu item,
     *   and the token `{label}` will be replaced by the label of the menu item.
     *   If this option is not set, [[linkTemplate]] will be used instead.
     * - options: array, optional, the HTML attributes for the menu item tag.
     *
     */
    public $items;

    /**
     * Allowed panel stypes
     */
    private static $_validTypes = [
        self::TYPE_DEFAULT,
        self::TYPE_SECONDARY,
        self::TYPE_PRIMARY,
        self::TYPE_INFO,
        self::TYPE_SUCCESS,
        self::TYPE_DANGER,
        self::TYPE_WARNING,
    ];

    public function init()
    {
        parent::init();
        $this->initBsVersion();
        if (empty($this->options['id'])) {
            $this->options['id'] = $this->getId();
        }
        $isBs4 = $this->isBs4();
        if (!isset($this->indMenuOpen)) {
            $this->indMenuOpen = $isBs4 ? '<i class="indicator fas fa-angle-down"></i>' :
                '<i class="indicator glyphicon glyphicon-chevron-down"></i>';
        }
        if (!isset($this->indMenuClose)) {
            $this->indMenuClose = $isBs4 ? '<i class="indicator fas fa-angle-right"></i>' :
                '<i class="indicator glyphicon glyphicon-chevron-right"></i>';
        }
        if (!isset($this->iconPrefix)) {
            $this->iconPrefix = $isBs4 ? 'fas fa-' : 'glyphicon glyphicon-';
        }
        if ($isBs4 && !isset($this->addlCssClass)) {
            $this->addlCssClass = 'text-secondary';
        }
        $this->activateParents = true;
        $css = $isBs4 ? '' : 'nav-stacked';
        $this->submenuTemplate = "\n<ul class='nav nav-pills {$css}'>\n{items}\n</ul>\n";
        $linkCss = static::getCssClass(self::BS_NAV_LINK);
        $this->linkTemplate = '<a href="{url}" class="' . $linkCss . '">{icon}{label}</a>';
        Html::addCssClass($this->itemOptions, static::getCssClass(self::BS_NAV_ITEM));
        $this->labelTemplate = '{icon}{label}';
        $this->markTopItems();
        $css = ['nav', 'nav-pills', static::getCssClass(self::BS_NAV_STACKED), 'kv-sidenav'];
        if ($isBs4) {
            $css[] = 'kv-sidenav-bs4';
        }
        if (empty($this->heading)) {
            $css[] = 'kv-no-heading';
        }
        Html::addCssClass($this->options, $css);
        $view = $this->getView();
        SideNavAsset::register($view);
        $id = $this->options['id'];
        $js = "kvSideNavInit('{$id}', '{$this->activeCssClass}', '{$this->addlCssClass}');";
        $view->registerJs($js);
    }

    /**
     * Renders the side navigation menu.
     * with the heading and panel containers
     */
    public function run()
    {
        $heading = '';
        $isBs4 = $this->isBs4();
        $type = in_array($this->type, self::$_validTypes) ? $this->type : self::TYPE_DEFAULT;
        $color = constant('self::BS_PANEL_' . strtoupper($type));
        $colorCss = static::getCssClass($color);
        if (!empty($this->heading)) {
            $css = [static::getCssClass(self::BS_PANEL_HEADING)];
            if ($isBs4) {
                $css = array_merge($css, explode(' ', $colorCss));
            }
            Html::addCssClass($this->headingOptions, $css);
            $title = $isBs4 ? $this->heading : Html::tag('h3', $this->heading,
                ['class' => static::getCssClass(self::BS_PANEL_TITLE)]);
            $heading = Html::tag('div', $title, $this->headingOptions);
        }
        $body = Html::tag('div', $this->renderMenu(), ['class' => 'table m-0']);
        $css = $isBs4 ? "border-{$type}" : $colorCss;
        Html::addCssClass($this->containerOptions, [static::getCssClass(self::BS_PANEL), $css]);
        echo Html::tag('div', $heading . $body, $this->containerOptions);
    }

    /**
     * Renders the main menu
     */
    protected function renderMenu()
    {
        if ($this->route === null && Yii::$app->controller !== null) {
            $this->route = Yii::$app->controller->getRoute();
        }
        if ($this->params === null) {
            $this->params = $_GET;
        }
        $items = $this->normalizeItems($this->items, $hasActiveChild);
        $options = $this->options;
        $tag = ArrayHelper::remove($options, 'tag', 'ul');

        return Html::tag($tag, $this->renderItems($items), $options);
    }

    /**
     * Marks each topmost level item which is not a submenu
     */
    protected function markTopItems()
    {
        $items = [];
        foreach ($this->items as $item) {
            if (empty($item['items'])) {
                $item['top'] = true;
            }
            $items[] = $item;
        }
        $this->items = $items;
    }

    /**
     * Appends or inserts a CSS class to a HTML tag markup
     * @param string $html
     * @param string | array $class
     * @param string $tag
     * @return string|string[]
     */
    protected static function setCssClass($html, $class, $tag = 'a')
    {
        if (is_array($class)) {
            $class = implode(' ', $class);
        }
        $flag = 'class="';
        $check = strpos($html, $tag);
        if ($check === false) {
            $flag = "class='";
            $check = strpos($html, $tag);
        }
        if ($check === false) {
            return str_ireplace("<{$tag} ", "<{$tag} class=\"{$class}\" ", $html);
        }
        return str_ireplace($flag, $flag . $class . ' ', $html);
    }

    /**
     * Renders the content of a side navigation menu item.
     *
     * @param array $item the menu item to be rendered. Please refer to [[items]] to see what data might be in the item.
     * @return string the rendering result
     * @throws InvalidConfigException
     */
    protected function renderItem($item)
    {
        $this->validateItems($item);
        $isBs4 = $this->isBs4();
        $template = ArrayHelper::getValue($item, 'template', $this->linkTemplate);
        if ($isBs4) {
            $template = static::setCssClass($template,
                ['nav-link', empty($item['active']) ? $this->addlCssClass : 'active']);
        }
        $url = Url::to(ArrayHelper::getValue($item, 'url', '#'));
        if (empty($item['top'])) {
            if (empty($item['items'])) {
                $template = str_replace('{icon}', $this->indItem . '{icon}', $template);
            } else {
                $template = static::setCssClass($template, 'kv-toggle');
                $openOptions = ($item['active']) ? ['class' => 'opened'] : [
                    'class' => 'opened',
                    'style' => 'display:none',
                ];
                $closeOptions = ($item['active']) ? [
                    'class' => 'closed',
                    'style' => 'display:none',
                ] : ['class' => 'closed'];
                $indicator = Html::tag('span', $this->indMenuOpen, $openOptions) . Html::tag('span',
                        $this->indMenuClose, $closeOptions);
                $template = str_replace('{icon}', $indicator . '{icon}', $template);
            }
        }
        $icon = empty($item['icon']) ? '' : '<span class="' . $this->iconPrefix . $item['icon'] . '"></span> &nbsp;';
        unset($item['icon'], $item['top']);
        return strtr($template, [
            '{url}' => $url,
            '{label}' => $item['label'],
            '{icon}' => $icon,
        ]);
    }

    /**
     * Validates each item for a valid label and url.
     *
     * @throws InvalidConfigException
     */
    protected function validateItems($item)
    {
        if (!isset($item['label'])) {
            throw new InvalidConfigException("The 'label' option is required.");
        }
    }
}