<?php

namespace PrestaShop\Module\Ovesio\Support;

trait TplSupport
{
    private function renderTemplate($template, $data = [])
    {
        $html = '';

        $file = _PS_MODULE_DIR_ . 'ovesio/views/templates/admin/' . $template;

        if (file_exists($file)) {
            extract($data);

            ob_start();
            include $file;
            $html = ob_get_contents();
            ob_end_clean();
        }

        return $html;
    }
}