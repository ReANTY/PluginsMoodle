<?php
// This file is part of Moodle - http://moodle.org/

namespace local_llmmotivation\hook;

defined('MOODLE_INTERNAL') || die();

/**
 * Hook subscriber for Moodle output footer.
 */
class footer {

    /**
     * Callback for before_footer_html_generation hook.
     */
    public static function before_footer_html_generation(\core\hook\output\before_footer_html_generation $hook): void {
        require_once(__DIR__ . '/../../lib.php');
        $html = local_llmmotivation_render_popups();
        if (!empty($html)) {
            $hook->add_html($html);
        }
    }

    /**
     * Callback for before_standard_footer_html_generation hook.
     */
    public static function before_standard_footer_html_generation(\core\hook\output\before_standard_footer_html_generation $hook): void {
        require_once(__DIR__ . '/../../lib.php');
        $html = local_llmmotivation_render_popups();
        if (!empty($html)) {
            $hook->add_html($html);
        }
    }
}
