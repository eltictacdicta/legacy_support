<?php
/**
 * This file is part of FSFramework originally based on Facturascript 2017
 * Copyright (C) 2025 Javier Trujillo <mistertekcom@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * Contract tests for the legacy auto-CRUD form renderer (fs_edit_form),
 * moved here from the core. It must keep emitting native date inputs (no
 * jQuery UI datepicker) and must escape model values and labels.
 */

namespace Tests\LegacySupport;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class LegacyEditFormTest extends TestCase
{
    protected function setUp(): void
    {
        require_once FS_FOLDER . '/plugins/legacy_support/base/fs_edit_form.php';
    }

    #[Test]
    public function dateColumnsEmitNativeInputsWithoutDatepicker(): void
    {
        $source = (string) file_get_contents(FS_FOLDER . '/plugins/legacy_support/base/fs_edit_form.php');

        $this->assertStringContainsString('type="date"', $source);
        $this->assertStringNotContainsString('datepicker', $source);
        $this->assertStringContainsString('date_to_iso', $source);
    }

    #[Test]
    public function textValuesAndLabelsAreEscaped(): void
    {
        $form = new \fs_edit_form();
        $form->add_column('nombre', 'string', '<b>Nombre</b>');

        $model = new \stdClass();
        $model->nombre = '"><script>alert(1)</script>';

        $html = $form->show('nombre', $form->columns['nombre'], $model);

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('<b>Nombre</b>', $html);
        $this->assertStringContainsString('&lt;b&gt;Nombre&lt;/b&gt;', $html);
    }

    #[Test]
    public function selectOptionsAreEscaped(): void
    {
        $form = new \fs_edit_form();
        $form->add_column_select('estado', ['ok' => '<script>x</script>'], 'Estado');

        $model = new \stdClass();
        $model->estado = 'ok';

        $html = $form->show('estado', $form->columns['estado'], $model);

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }
}
