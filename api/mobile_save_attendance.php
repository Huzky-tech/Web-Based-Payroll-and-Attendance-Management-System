<?php
/**
 * Stateless mobile entry point for the offline attendance queue.
 * The Web System applies browser session/CSRF checks to non-mobile filenames.
 * save_attendance.php still validates the supplied timekeeper and assigned site.
 */
require_once __DIR__ . '/save_attendance.php';
