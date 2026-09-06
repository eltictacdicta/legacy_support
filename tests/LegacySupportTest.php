<?php

/**
 * This file is part of FSFramework originally based on Facturascript 2017
 * Copyright (C) 2025 Javier Trujillo <mistertekcom@gmail.com>
 * Copyright (C) 2013-2020 Carlos Garcia Gomez <neorazorx@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
/**
 * Contract tests for the legacy_support asset injection: the plugin must
 * provide the jQuery UI / bootstrap-datepicker / autocomplete / shake stack
 * through the generic head_extra_js and head_extra_css header globals, and
 * legacy-init.js must reproduce the old core datepicker auto-init without the
 * type="date" downgrade (that behavior belongs to the core native-input
 * migration and must not come back).
 *
 * Phase 6: legacy-base.js also receives the helpers moved out of core
 * view/js/base.js (init_modal_iframe, ajax_form, fs_confirm, fs_alert,
 * fs_prompt plus the ready-block parts core no longer ships), loading last
 * in legacyHeadJs() and still before core base.js.
 */

namespace Tests\LegacySupport;

use FSFramework\Plugins\legacy_support\Init;
use PHPUnit\Framework\TestCase;

class LegacySupportTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once FS_FOLDER . '/plugins/legacy_support/Init.php';
    }

    // =====================================================================
    // fsframework.ini metadata
    // =====================================================================

    public function testPluginMetadataParsesAndDeclaresTheLegacyStack(): void
    {
        $path = FS_FOLDER . '/plugins/legacy_support/fsframework.ini';
        $this->assertFileExists($path);

        $metadata = parse_ini_file($path, false, INI_SCANNER_TYPED);

        $this->assertIsArray($metadata);
        $this->assertSame('legacy_support', $metadata['name'] ?? null);
        $this->assertNotEmpty($metadata['version'] ?? null);
        $this->assertNotEmpty($metadata['min_version'] ?? null);
        $this->assertEmpty($metadata['require'] ?? '');
        $this->assertStringContainsStringIgnoringCase('legacy', (string) ($metadata['description'] ?? ''));
        $this->assertStringContainsString('jQuery UI', (string) ($metadata['description'] ?? ''));
        $this->assertStringContainsString('bootstrap-datepicker', (string) ($metadata['description'] ?? ''));
        $this->assertStringContainsString('Javier Trujillo', (string) ($metadata['author'] ?? ''));
    }

    // =====================================================================
    // Asset lists (public/static, testable without the Twig event)
    // =====================================================================

    public function testLegacyHeadJsProvidesTheExactAssetList(): void
    {
        $this->assertSame(
            [
                'view/js/bootstrap-datepicker.js',
                'view/js/jquery-ui.min.js',
                'view/js/jquery.autocomplete.min.js',
                'view/js/jquery.ui.shake.js',
                'plugins/legacy_support/view/js/legacy-init.js',
                'plugins/legacy_support/view/js/legacy-base.js',
            ],
            Init::legacyHeadJs()
        );
    }

    public function testLegacyHeadCssProvidesTheExactAssetList(): void
    {
        $this->assertSame(
            ['view/css/datepicker.css'],
            Init::legacyHeadCss()
        );
    }

    public function testDeclaredLegacyAssetsExistOnDisk(): void
    {
        foreach (array_merge(Init::legacyHeadJs(), Init::legacyHeadCss()) as $url) {
            $this->assertFileExists(FS_FOLDER . '/' . $url, $url . ' must stay on disk for old plugins');
        }
    }

    public function testInitWiresTheHeadExtraGlobals(): void
    {
        $source = (string) file_get_contents(FS_FOLDER . '/plugins/legacy_support/Init.php');

        $this->assertStringContainsString("addGlobal('head_extra_js', self::legacyHeadJs())", $source);
        $this->assertStringContainsString("addGlobal('head_extra_css', self::legacyHeadCss())", $source);
    }

    public function testPluginVersionIsBumpedTo120ForTheBaseJsMove(): void
    {
        $metadata = parse_ini_file(FS_FOLDER . '/plugins/legacy_support/fsframework.ini', false, INI_SCANNER_TYPED);

        $this->assertIsArray($metadata);
        $this->assertSame('1.2.0', $metadata['version'] ?? null);
    }

    // =====================================================================
    // legacy-init.js contract
    // =====================================================================

    public function testLegacyInitInitializesDatepickerInputs(): void
    {
        $source = (string) file_get_contents(FS_FOLDER . '/plugins/legacy_support/view/js/legacy-init.js');

        $this->assertStringContainsString("$('.datepicker')", $source);
        $this->assertStringContainsString('$(document).ready', $source);
        $this->assertStringContainsString("format: 'dd-mm-yyyy'", $source);
    }

    public function testLegacyInitDoesNotDowngradeNativeDateInputs(): void
    {
        $source = (string) file_get_contents(FS_FOLDER . '/plugins/legacy_support/view/js/legacy-init.js');

        $this->assertStringNotContainsString("attr('type', 'text'", $source);
        $this->assertStringNotContainsString('input[type="date"]', $source);
    }

    public function testLegacyInitIsCspSafe(): void
    {
        $source = (string) file_get_contents(FS_FOLDER . '/plugins/legacy_support/view/js/legacy-init.js');

        $this->assertStringNotContainsString('eval(', $source);
        $this->assertStringNotContainsString('new Function(', $source);
    }

    // =====================================================================
    // legacy-base.js contract (moved from core view/js/base.js)
    // =====================================================================

    public function testLegacyBaseJsDefinesTheMovedCoreHelpers(): void
    {
        $path = FS_FOLDER . '/plugins/legacy_support/view/js/legacy-base.js';
        $this->assertFileExists($path);

        $source = (string) file_get_contents($path);

        foreach (['init_modal_iframe', 'ajax_form', 'fs_confirm', 'fs_alert', 'fs_prompt'] as $symbol) {
            $this->assertMatchesRegularExpression(
                '/function ' . $symbol . '\(/',
                $source,
                'legacy-base.js must define ' . $symbol . ' for old plugins'
            );
        }
    }

    public function testLegacyBaseJsReproducesTheMovedReadyBlock(): void
    {
        $source = (string) file_get_contents(FS_FOLDER . '/plugins/legacy_support/view/js/legacy-base.js');

        $this->assertStringContainsString('$(document).ready', $source);
        $this->assertStringContainsString('init_modal_iframe();', $source);
        $this->assertStringContainsString('[data-confirm]', $source);
        $this->assertStringContainsString('data-toggle="tooltip"', $source);
        $this->assertStringContainsString('data-toggle="popover"', $source);
        // The modal autofocus stays in core base.js (core templates emit modals).
        $this->assertStringNotContainsString("$('.modal').on('shown.bs.modal'", $source);
    }
}
