<?php
require_once 'config.php';
fermerApercuBackOffice();
if (session_name() === APERCU_SESSION) { detruireSessionCourante(); }
header('Location: ./');
exit();
