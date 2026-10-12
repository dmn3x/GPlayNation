<?php

/**
 * Vercel serverless entry point.
 *
 * Vercel executes PHP files inside api/ as serverless functions.
 * This file simply forwards every request to the standard Laravel
 * front controller.
 */
require __DIR__.'/../public/index.php';
