<?php

require_once 'vendor/fpdf/fpdf.php';

$pdf = new FPDF();

$pdf->AddPage();

$pdf->SetFont(
'Arial',
'B',
16
);

$pdf->Cell(
40,
10,
'Hello PDF'
);

$pdf->Output();