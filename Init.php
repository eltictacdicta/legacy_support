<?php
/**
 * This file is part of FSFramework
 * Copyright (C) 2025 Javier Trujillo <mistertekcom@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 */

namespace FSFramework\Plugins\legacy_support;

use FSFramework\Event\FSEventDispatcher;
use FSFramework\Event\TwigLoaderEvent;
use FSFramework\Event\TwigInitEvent;

/**
 * Initialization class for legacy_support plugin.
 * Provides:
 * - RainTPL to Twig translator for legacy .html templates
 * - PHP functions as Twig functions (used by translated RainTPL templates)
 * - Legacy frontend assets (jQuery UI, bootstrap-datepicker, autocomplete,
 *   shake, and the legacy base.js helpers moved out of core view/js/base.js)
 *   injected into the global header via the head_extra_js /
 *   head_extra_css globals, for old plugin views that still need them
 */
class Init
{
    private const EOL_TARGET_VERSION = '3.0';

    public function init(): void
    {
        self::emitDeprecationNotice();
        $dispatcher = FSEventDispatcher::getInstance();

        // Subscribe to the Twig loader initialization event
        $dispatcher->addListener(TwigLoaderEvent::NAME, function (TwigLoaderEvent $event) {
            $loader = $event->getLoader();

            // Wrap the loader with our RainTPL translator
            LegacyUsageTracker::incrementLegacyComponent('legacy_support.loader', 'twig_loader_init');
            $event->setLoader(new \FSFramework\Plugins\legacy_support\Template\LegacyFilesystemLoader($loader));
        });

        // Subscribe to Twig init to register PHP functions and legacy asset globals
        $dispatcher->addListener(TwigInitEvent::NAME, function (TwigInitEvent $event) {
            $twig = $event->getTwig();
            $this->registerPhpFunctions($twig);
            $twig->addGlobal('head_extra_js', self::legacyHeadJs());
            $twig->addGlobal('head_extra_css', self::legacyHeadCss());
        });
    }

    /**
     * Legacy JS assets for the global header, in load order. bootstrap-datepicker
     * deliberately loads BEFORE jquery-ui.min.js, matching the order the removed
     * core header tags used, so $.fn.datepicker keeps resolving to the jQuery UI
     * widget (same winner as before) and old plugin behavior stays equivalent.
     *
     * legacy-base.js (dialog/modal/tooltip helpers moved from core base.js)
     * loads last, after legacy-init.js and still before core base.js, like the
     * old core base.js did.
     *
     * URLs are root-relative and rendered with the FS_PATH prefix in
     * header.html.twig, same scheme as the business_data_js global.
     *
     * @return list<string>
     */
    public static function legacyHeadJs(): array
    {
        return [
            'view/js/bootstrap-datepicker.js',
            'view/js/jquery-ui.min.js',
            'view/js/jquery.autocomplete.min.js',
            'view/js/jquery.ui.shake.js',
            'plugins/legacy_support/view/js/legacy-init.js',
            'plugins/legacy_support/view/js/legacy-base.js',
        ];
    }

    /**
     * Legacy CSS assets for the global header (bootstrap-datepicker + jQuery UI
     * datepicker skins). Root-relative, same URL scheme as legacyHeadJs().
     *
     * @return list<string>
     */
    public static function legacyHeadCss(): array
    {
        return [
            'view/css/datepicker.css',
        ];
    }

    /**
     * Register PHP functions as Twig functions for legacy template compatibility
     * RainTPL templates use {function="php_func($var)"} which get translated to {{ php_func(var) }}
     */


    /**
     * @deprecated plugin legacy_support será retirado en v3.0.
     *             Migración: usar plantillas Twig nativas (.html.twig) y controladores FSRoute.
     */
    private static function emitDeprecationNotice(): void
    {
        @trigger_error(
            'legacy_support está deprecado y se retirará en v' . self::EOL_TARGET_VERSION
            . '. Migración recomendada: plantillas Twig (.html.twig) + rutas/controladores modernos.',
            E_USER_DEPRECATED
        );
    }

    private function registerPhpFunctions(\Twig\Environment $twig): void
    {
        $phpFunctions = [
            'constant',
            'in_array',
            'file_exists',
            'class_exists',
            'count',
            'is_array',
            'is_null',
            'strlen',
            'substr',
            'strpos',
            'strtolower',
            'strtoupper',
            'ucfirst',
            'trim',
            'implode',
            'explode',
            'array_key_exists',
            'number_format',
            'date',
            'time',
            'strtotime',
            'json_encode',
            'json_decode',
            'htmlspecialchars',
            'nl2br',
            'round',
            'ceil',
            'floor',
            'abs',
            'min',
            'max',
            'sprintf',
            'preg_match',
            'mt_rand',
            'join',
            'intval',
            'floatval',
            'mb_substr',
            'mb_strlen',
            'mb_strpos',
            'mb_strtolower',
            'mb_strtoupper',
            'base64_encode',
            'base64_decode',
            'urlencode',
            'urldecode',
            'rawurlencode',
            'rawurldecode',
            'http_build_query',
        ];

        foreach ($phpFunctions as $func) {
            try {
                $twig->addFunction(new \Twig\TwigFunction($func, $func));
            } catch (\LogicException $e) {
                // Function already registered
            }
        }

        // Register PHP language constructs with wrapper closures
        $twig->addFunction(new \Twig\TwigFunction('isset', fn($var) => isset($var)));
        $twig->addFunction(new \Twig\TwigFunction('empty', fn($var) => empty($var)));
        $twig->addFunction(new \Twig\TwigFunction('defined', fn($name) => defined($name)));
        $twig->addFunction(new \Twig\TwigFunction('array', fn(...$args) => $args));
        $twig->addFunction(new \Twig\TwigFunction('auto_ext', function ($file) {
            if (empty($file)) {
                return $file;
            }
            return str_ends_with($file, '.html') ? $file : $file . '.html';
        }));
    }
}
