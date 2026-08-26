<?php
require_once __DIR__ . '/auth.php';
logout();
redirect(base_url('login.php'));
