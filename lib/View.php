<?php

declare(strict_types=1);

namespace lib;

use Lib\Core\View as BaseView;

/**
 * @deprecated Use Lib\Core\View instead.
 */
class View
{
    private static array $baseCss = [];
    private static array $baseJs = [];

    public static function share(string $key, mixed $value): void
    {
        BaseView::share($key, $value);
    }

    public static function component(string $view, array $data = []): string
    {
        return BaseView::component($view, $data);
    }

    public static function render(string $view, array $data = []): string
    {
        return BaseView::render($view, $data);
    }

    public static function exists(string $view): bool
    {
        return BaseView::exists($view);
    }

    public static function head(?string $title = null, ?string $description = null, ?string $icon = null): string
    {
        $title ??= BaseView::getShared('page.title') ?? config('app.name');
        $description ??= BaseView::getShared('page.description') ?? '';
        $icon ??= BaseView::getShared('company.favicon') ?? asset('/gam.ico');

        return "
            <meta charset='utf-8'>
            <meta content='width=device-width, initial-scale=1.0' name='viewport'>
            <title>" . e($title) . "</title>
            <meta name='description' content='" . e($description) . "'>
            <link rel='icon' type='image/x-icon' href='" . e($icon) . "'/>
        ";
    }

    public static function css(array $extra = []): string
    {
        $links = array_merge(self::$baseCss, $extra);
        return implode("\n", array_map(fn ($url) => "<link rel='stylesheet' href='" . e($url) . "'>", $links));
    }

    public static function js(array $extra = []): string
    {
        $links = array_merge(self::$baseJs, $extra);
        return implode("\n", array_map(fn ($url) => "<script src='" . e($url) . "'></script>", $links));
    }

    public static function setBaseCss(array $css): void
    {
        self::$baseCss = $css;
    }

    public static function setBaseJs(array $js): void
    {
        self::$baseJs = $js;
    }
}
