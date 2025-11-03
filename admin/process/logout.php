<?php
session_start();
session_destroy(); // End session
header("Location: ../login"); // Redirect to login page
exit();
