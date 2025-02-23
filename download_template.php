<?php
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="template_anggota.csv"');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Output template
echo "NRP;Nama Lengkap;Pangkat;Korps;Jabatan;Status;Tanggal Bergabung\n";
exit; 