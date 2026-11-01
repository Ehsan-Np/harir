<?php
/**
 * صفحهٔ محصول تکی — fallback بومی.
 *
 * اگر تمپلیت «Single Product» المنتور Pro وجود داشته باشد، المنتور
 * کنترل را در دست می‌گیرد؛ در غیر این صورت این فایل اجرا می‌شود.
 * چیدمان مطابق `01-retail-product.html`.
 *
 * @package Zarincode_Harir
 */

defined( 'ABSPATH' ) || exit;

get_header();

woocommerce_content();

get_footer();
