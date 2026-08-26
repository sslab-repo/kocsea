<?php require_once __DIR__ . '/auth.php';
if (current_user()) { redirect(base_url('dashboard.php')); }
redirect(base_url('login.php'));
