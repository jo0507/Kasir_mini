<?php
/**
 * Vercel Speed Insights Integration
 * 
 * This file includes the Vercel Speed Insights tracking script
 * to monitor web performance metrics for the application.
 * 
 * Include this file in the <head> section of your HTML pages.
 */
?>
<script>
  window.si = window.si || function () { (window.siq = window.siq || []).push(arguments); };
</script>
<script defer src="/_vercel/speed-insights/script.js"></script>
